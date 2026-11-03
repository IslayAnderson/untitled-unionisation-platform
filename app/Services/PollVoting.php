<?php

namespace App\Services;

use App\Exceptions\PollRefused;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Poll;
use App\Models\PollVoter;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creating, voting in and closing secret-ballot polls. See docs/architecture/polls.md.
 */
class PollVoting
{
    /**
     * @param  list<string>  $answers
     *
     * @throws PollRefused
     */
    public function create(
        Contact $author,
        Bulletin $bulletin,
        string $title,
        ?string $description,
        array $answers,
        bool $allowsMultiple,
        CarbonInterface $closesAt,
    ): Poll {
        if ($bulletin->author_id !== $author->id || $bulletin->isVouching()) {
            throw new PollRefused('Only the author of a bulletin can add a poll to it.');
        }

        if (count($answers) < 2) {
            throw new PollRefused('A poll needs at least two answers.');
        }

        if ($closesAt->isPast()) {
            throw new PollRefused('A poll has to close in the future.');
        }

        return DB::transaction(function () use ($bulletin, $title, $description, $answers, $allowsMultiple, $closesAt) {
            $poll = $bulletin->polls()->create([
                'title' => $title,
                'description' => $description,
                'allows_multiple' => $allowsMultiple,
                'closes_at' => $closesAt,
            ]);

            $poll->answers()->createMany(collect($answers)->values()->map(fn (string $answer, int $position) => [
                'position' => $position,
                'title' => $answer,
            ]));

            return $poll;
        });
    }

    /**
     * Record a secret ballot. The voter row and the totals are written
     * separately and without timestamps, so nothing links the member to
     * their choice. Ballots can't be changed.
     *
     * @param  list<int>  $answerIds
     *
     * @throws PollRefused
     */
    public function vote(Contact $voter, Poll $poll, array $answerIds): void
    {
        if (! $voter->isEnrolled() || $poll->bulletin->building_id !== $voter->building_id) {
            throw new PollRefused('Only members of the union can vote.');
        }

        $answerIds = array_values(array_unique(array_map('intval', $answerIds)));

        DB::transaction(function () use ($voter, $poll, $answerIds) {
            $poll = Poll::lockForUpdate()->findOrFail($poll->id);

            if (! $poll->isOpen()) {
                throw new PollRefused('This poll has closed.');
            }

            if ($answerIds === []) {
                throw new PollRefused('Choose an answer.');
            }

            if (! $poll->allows_multiple && count($answerIds) > 1) {
                throw new PollRefused('Choose one answer.');
            }

            if ($poll->answers()->whereKey($answerIds)->count() !== count($answerIds)) {
                throw new PollRefused('That answer isn\'t part of this poll.');
            }

            try {
                PollVoter::create(['poll_id' => $poll->id, 'contact_id' => $voter->id]);
            } catch (UniqueConstraintViolationException) {
                throw new PollRefused('You\'ve already voted in this poll.');
            }

            // The query builder, so updated_at isn't touched: it would record when each vote was cast.
            DB::table('poll_answers')->whereIn('id', $answerIds)->increment('vote_count');
            DB::table('polls')->where('id', $poll->id)->increment('ballot_count');
        });
    }

    /**
     * Close a poll before its deadline.
     *
     * @throws PollRefused
     */
    public function close(Contact $author, Poll $poll): void
    {
        if ($poll->bulletin->author_id !== $author->id) {
            throw new PollRefused('Only the author of the bulletin can close its poll.');
        }

        if (! $poll->isOpen()) {
            throw new PollRefused('This poll has already closed.');
        }

        $poll->update(['closed_at' => now()]);
    }
}

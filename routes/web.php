<?php

use App\Http\Controllers\AddressSearchController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\BulletinReplyController;
use App\Http\Controllers\ConstitutionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\MailingListController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\ReferendumController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\ThreadReplyController;
use App\Http\Controllers\UnionMailController;
use App\Http\Controllers\VouchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/address/search', AddressSearchController::class)
    ->middleware('throttle:30,1')
    ->name('address.search');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/join/{building:signup_token}', [RegisterController::class, 'createJoin'])->name('join');
    Route::post('/join/{building:signup_token}', [RegisterController::class, 'storeJoin']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/unit', [OnboardingController::class, 'storeUnit'])->name('onboarding.unit');
    Route::post('/onboarding/building', [OnboardingController::class, 'storeBuilding'])->name('onboarding.building');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/vouch/{contact:vouch_token}', [VouchController::class, 'show'])->name('vouch.show');
    Route::post('/vouch/{contact:vouch_token}', [VouchController::class, 'store'])->name('vouch.store');

    // Tenants waiting to be vouched for can read the constitution they're joining under.
    Route::get('/constitution', [ConstitutionController::class, 'show'])->name('constitution.show');

    // Open to tenants waiting to be vouched for, but only for the vouching bulletin (the view-bulletin gate).
    Route::get('/bulletins/{bulletin}', [BulletinController::class, 'show'])->whereNumber('bulletin')->name('bulletins.show');
    Route::post('/bulletins/{bulletin}/replies', [BulletinReplyController::class, 'store'])
        ->whereNumber('bulletin')
        ->middleware('throttle:30,1')
        ->name('bulletins.replies.store');
});

Route::middleware(['auth', 'verified', 'member'])->group(function () {
    Route::resource('contacts', ContactController::class)->except('show');
    Route::resource('landlords', LandlordController::class)->except(['index', 'show']);

    Route::patch('/mailing-lists/reply-to', [MailingListController::class, 'updateReplyTo'])->name('mailing-lists.reply-to');
    Route::resource('mailing-lists', MailingListController::class);

    Route::resource('bulletins', BulletinController::class)->only(['index', 'create', 'store']);
    Route::get('/bulletins/{bulletin}/mail', [BulletinController::class, 'composeMail'])->name('bulletins.mail.create');
    Route::post('/bulletins/{bulletin}/mail', [BulletinController::class, 'mail'])
        ->middleware('throttle:10,1')
        ->name('bulletins.mail');
    Route::resource('threads', ThreadController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/threads/{thread}/replies', [ThreadReplyController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('threads.replies.store');

    Route::get('/constitution/amend', [ConstitutionController::class, 'create'])->name('constitution.amend');
    Route::post('/constitution/amend', [ConstitutionController::class, 'store'])->middleware('throttle:10,1')->name('constitution.propose');
    Route::get('/constitution/versions/{constitution}', [ConstitutionController::class, 'version'])->name('constitution.versions.show');

    Route::resource('policies', PolicyController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/policies/{policy}/revise', [PolicyController::class, 'revise'])->name('policies.revise');
    Route::get('/policies/{policy}/repeal', [PolicyController::class, 'confirmRepeal'])->name('policies.repeal.confirm');
    Route::post('/policies/{policy}/repeal', [PolicyController::class, 'repeal'])->middleware('throttle:10,1')->name('policies.repeal');

    Route::get('/referendums', [ReferendumController::class, 'index'])->name('referendums.index');
    Route::get('/referendums/{referendum}', [ReferendumController::class, 'show'])->name('referendums.show');
    Route::post('/referendums/{referendum}/votes', [ReferendumController::class, 'vote'])->middleware('throttle:30,1')->name('referendums.votes.store');
    Route::post('/referendums/{referendum}/withdraw', [ReferendumController::class, 'withdraw'])->name('referendums.withdraw');

    Route::get('/bulletins/{bulletin}/polls/create', [PollController::class, 'create'])->name('bulletins.polls.create');
    Route::post('/bulletins/{bulletin}/polls', [PollController::class, 'store'])->middleware('throttle:10,1')->name('bulletins.polls.store');
    Route::post('/polls/{poll}/votes', [PollController::class, 'vote'])->middleware('throttle:30,1')->name('polls.votes.store');
    Route::post('/polls/{poll}/close', [PollController::class, 'close'])->name('polls.close');
    Route::post('/uploads/images', [ImageUploadController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('uploads.images.store');

    Route::get('/mail', [UnionMailController::class, 'index'])->name('mail.index');
    Route::get('/mail/create', [UnionMailController::class, 'create'])->name('mail.create');
    Route::post('/mail', [UnionMailController::class, 'store'])->middleware('throttle:10,1')->name('mail.store');
    Route::get('/mail/{unionMail}', [UnionMailController::class, 'show'])->name('mail.show');
    Route::get('/mail/{unionMail}/attachments/{attachment}', [UnionMailController::class, 'attachment'])
        ->scopeBindings()
        ->name('mail.attachments.show');
    Route::post('/mail/{unionMail}/decision', [UnionMailController::class, 'decide'])->name('mail.decide');
    Route::post('/mail/{unionMail}/withdraw', [UnionMailController::class, 'withdraw'])->name('mail.withdraw');
});

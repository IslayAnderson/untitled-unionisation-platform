# Front end: the GOV.UK Design System

Every page is built with the [GOV.UK Design System](https://design-system.service.gov.uk/): the
`govuk-frontend` package's styles, components and JavaScript, following its patterns and content style.
There is no other CSS framework.

## Not a GOV.UK service

The platform isn't on GOV.UK, so, as the Design System requires of services that aren't:

* the header is the **Generic header** with the platform's name, not the GOV.UK header: no crown and no
  GOV.UK logotype;
* the font is Arial (`$govuk-font-family`), not GDS Transport, which only GOV.UK may use;
* the footer has no crown or Crown copyright, and says the platform isn't a government service.

## Build

* `resources/scss/app.scss` loads `pkg:govuk-frontend` (Sass's Node package importer, set up in
  `vite.config.js`) and then the app's own components.
* `resources/js/app.js` starts Alpine, then calls `initAll()` from `govuk-frontend` for the components that
  need JavaScript (error summary, service navigation menu, notification banners, character count, radios and
  checkboxes, buttons, file upload).
* The minifier drops GOV.UK Frontend's old Internet Explorer media query hacks
  (`css.lightningcss.errorRecovery`).

## Page structure

`resources/views/components/layouts/app.blade.php` is the GOV.UK page template:

* skip link, Generic header, then **Service navigation** (members see the union's sections; admins also
  see Admin);
* an optional **Back link** (`back-link` prop);
* in `main`: the demo **Notification banner**, the **Error summary** when the request has validation errors
  (and "Error: " in the page title), a success banner for `session('status')` and a neutral "Important"
  banner for `session('notice')`, which is used for refusals;
* the footer.

Pages put their content in `govuk-grid-row` / `govuk-grid-column-two-thirds`, start with a caption where it
helps and one `govuk-heading-l`, and use `govuk-body`, `govuk-link`, lists, **Inset text**, **Warning text**,
**Summary list**, **Table**, **Tag** and **Details** as the Design System describes. Calls to action that
go to another page are links styled as buttons (`role="button"`); actions that change something are forms.

## Form components

Blade components in `resources/views/components` render the Design System's form components, with the
label, hint, error message and `aria-describedby` wiring each needs:

| Component | Design System component |
| --- | --- |
| `x-field` | Text input (`width` takes a width class suffix, such as `20` or `two-thirds`) |
| `x-textarea` | Textarea, or Character count with `maxlength` |
| `x-select` | Select |
| `x-checkbox` | Checkboxes, with a single item |
| `x-button` | Button (`variant`: secondary, warning or inverse) |
| `x-attachments-field` | File upload |
| `x-rich-editor` | Textarea, turned into the TinyMCE editor |
| `x-privacy-reminder` | Warning text |
| `x-referendum-status`, `x-mail-status` | Tag |
| `x-rules-table` | Summary list |

An error message's id is the field's id plus `-error`. The field's id is its name with `.` and `[` turned
into `_`; the error summary links to it, and an error on one item of an array field (`answers.2`) links to
the field as a whole. Forms use `novalidate`, so the server's errors, in the Design System's style, are the
ones people see.

### Error messages

Validation messages follow the Design System's
[error message](https://design-system.service.gov.uk/components/error-message/) style: they say what to do
("Enter a title", "Select a mailing list", "Title must be 200 characters or fewer"), without "field",
"invalid" or a full stop.

* `lang/en/validation.php` is merged over Laravel's messages. Its `custom` section covers the fields forms
  share (title, body, reply, email, mailing list and so on); its general messages are the fallback.
* A controller passes its own messages where a shared field means something else on its form, such as a
  policy's title or a poll's question.
* `App\Rules\FilledRichText` fails with the field's "required" message, so an editor holding only empty
  paragraphs reads as empty.
* `lang/en/auth.php` does the same for login errors.

Refusals from the domain services (such as "You've already voted in this referendum.") are sentences
explaining why, and are shown as they are.

Radios and checkboxes written straight into a page (votes, poll answers, mailing list contacts, the
printables options) follow the same markup inside a fieldset with a legend.

## The app's own components

Where the Design System has no component, the app adds one in `resources/scss/components`, named
`app-*` and built from GOV.UK Frontend's helpers (colours, spacing, typography, media queries), so it
looks and behaves like the rest:

| Class | Use |
| --- | --- |
| `app-item-list` | Lists of bulletins, discussions, referendums, policies, roles, mail and contacts: a linked title, tags, a line of details and a summary, ruled like Summary list rows |
| `app-reply`, `app-reply-list` | A post in a bulletin's or discussion's replies |
| `app-rich-text` | Stored rich text, in GOV.UK typography |
| `app-rich-editor` | The TinyMCE editor's border, focus and error states |
| `app-poll-results` | A closed poll's results as bars |
| `app-text-diff` | A proposed change to a policy or the constitution, paragraph by paragraph |
| `app-qr-code`, `app-url` | A QR code to scan from the screen, and the URL under it |
| `app-address-search` | The address suggestions under the building address input |
| `app-link-button` | A form button that looks like a link (log out, step down, delete in a row of actions) |
| `app-related` | Related links beside the main column |
| `app-sub-navigation` | Links between the admin pages |
| `app-table-wrapper` | Lets a wide table scroll sideways on small screens |
| `app-invite-*` | The printable posters, flyers, door slips and DL letters (sized in millimetres and container query units so the screen preview matches the printed sheet) |

## Not covered

Emails and the PDF exports have their own inline styles, since email clients and the PDF renderer can't
load the stylesheet.

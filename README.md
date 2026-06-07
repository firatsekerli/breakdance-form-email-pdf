# Breakdance Form PDF

A WordPress plugin that adds a standalone **"Send PDF"** action to Breakdance
forms — fully configured in the Breakdance UI, with no hardcoded fields or
branding.

On submission it:

1. Evaluates a set of **conditional rules** (each rule = a condition on a form
   field) to choose **the recipient** and **the rich-text content** for the
   document.
2. Renders that content into a **branded PDF** (mPDF).
3. Emails it with a **short summary in the body**, the **PDF attached**, and
   **uploaded files attached alongside** (optional).

It is standalone and does **not** modify or replace any other email/notification
action you may have. Add "Send PDF" alongside your existing _Actions After
Submission_.

---

## How it works (UI-driven)

The action is built on the Breakdance Forms Action API:

- Registered on `breakdance_loaded` (with version fallbacks).
- `controls()` builds the Form Builder UI.
- Field pickers and the `{ }` variable buttons populate from the form's own
  fields (`content.form.fields`), so nothing is hardcoded.
- `run($form, $settings, $extra)` resolves `{field_id}` tokens, renders the
  PDF, and sends the email.

### The controls

**PDF Content & Recipient Rules**
- **Rules** (repeater) — each rule has:
  - **Field** / **Condition** / **Value** — when this rule matches
    (e.g. `request_reason` *Equals* `Service Call Request`).
  - **Send To** — recipient for this rule.
  - **PDF Content** — rich text with the `{field}` variable picker (and an
    `{all_fields}` token). This is what gets rendered into the PDF.
  - First matching rule wins.
- **Default Recipient** — used when no rule matches.
- **Default PDF Content** — used when no rule matches.
- **PDF Title** — heading at the top of the PDF (supports field variables).

**Email Settings**
- **Subject**, **From Email**, **From Name**, **Reply To**, **CC**, **BCC**
  (all support field variables where relevant).
- **Email Body (summary)** — short rich-text summary shown in the email body
  (full detail lives in the attached PDF).
- **Attach uploaded files** — also attach any files the visitor uploaded.

**PDF Appearance**
- **Logo URL** — optional logo in the PDF header (blank → the site name is shown).
- **Brand Colour** — hex colour for the header rule and title.
- **Footer Text** — optional footer line (blank → the site name is used).

### Per-condition content

Add one rule per condition, each with the field/condition/value that should
match and the matching PDF content. For example, paste into a rule's **PDF
Content**:

```html
<div style="font-size:18px;"><strong>Request Reason:</strong> {request_reason}</div>
<div style="font-size:18px;"><strong>Full Name:</strong> {full_name}</div>
<div style="font-size:18px;"><strong>Email:</strong> {email}</div>
... etc ...
```

(Use the variable picker to insert tokens instead of typing them.) Set the
**Default PDF Content** so unmatched submissions still produce a sensible PDF.

---

## Installation

```bash
composer install --no-dev
```

Upload the whole plugin folder (including `vendor/`) to `wp-content/plugins/`
and activate it, **or** zip it and install via _Plugins → Add New → Upload_.

> Requires mPDF (installed via Composer into `vendor/`). If it's missing the
> plugin shows an admin notice and the action does nothing. If your server
> already provides mPDF, this bundled copy does not conflict (Composer
> autoloading is lazy and guarded by `class_exists`).

Then, in the Form Builder, open **Actions After Submission** → add **Send PDF**,
and configure the rules, email settings, and appearance.

---

## Filters

| Filter | Purpose |
|--------|---------|
| `bd_form_pdf_logo` | Override the PDF logo URL/path. |
| `bd_form_pdf_brand_colour` | Override the brand colour (hex). |
| `bd_form_pdf_footer` | Override the footer text (`$footer, $siteName`). |

Branding defaults to your WordPress site name/URL and the WordPress admin blue
(`#2271b1`) when not set in the UI.

The template lives in [`templates/pdf.php`](templates/pdf.php).

---

## Development / testing

Render sample PDFs without a WordPress install:

```bash
composer install
php test/render-sample.php           # writes to a temp folder
php test/render-sample.php ./out     # or a folder of your choosing
```

The harness stubs the few WordPress functions the PDF code uses and feeds in
sample rendered content.

---

## File structure

```
breakdance-form-pdf.php      Plugin bootstrap + registration (breakdance_loaded)
src/Actions/SendPdf.php      The Send PDF action: controls() + run() + rule logic
src/PdfBuilder.php           mPDF rendering + branded shell
templates/pdf.php            Branded PDF HTML/CSS
test/render-sample.php       Standalone PDF render harness
vendor/                      Composer dependencies (mPDF) — committed, upload-ready
```

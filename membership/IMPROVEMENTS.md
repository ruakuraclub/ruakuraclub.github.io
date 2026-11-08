# Future improvements

Ideas agreed but not built yet.

## Reports

### PDF download button (jsPDF)

At the moment "Print / save as PDF" opens the browser's print dialog, and the user picks "Microsoft Print to PDF" / "Save as PDF". A direct PDF download would be nicer.

**Plan:** use [jsPDF](https://github.com/parallax/jsPDF) with the [jspdf-autotable](https://github.com/simonbengtsson/jsPDF-AutoTable) plugin. This is the same library the online application form already uses. The button reads the report table on the page and builds the PDF in the browser.

- Gives a direct download with a proper file name (e.g. `pool-access-2025-26.pdf`). Looks the same in every browser.
- Allows page numbers, the club logo and title on each page, landscape for wide reports, repeated column headings and shaded rows.
- **Embed a Unicode font (e.g. Noto Sans).** jsPDF's built-in fonts can't show macrons (ā, ē, ō…), which matters for NZ names. Load the font only when the PDF button is clicked.
- **Keep copies of the `.min.js` files (and font) in `public/assets/`.** The site's Content-Security-Policy only allows scripts from its own domain. No Node needed.
- Keep the existing print button for quick paper copies.

Alternative considered: **dompdf** (server-side, via Composer). It handles macrons with its included font and needs no JavaScript, but gives less control over headers and footers, and adds a few MB to `vendor/`.

### Filters on pool and tennis reports

Filter by key status, bands status and bands returned. The status, date and count are already separate columns ready for this.

## Exports

All four are built: **Print cards** (`RuakuraClub.xlsx` for the POINTMAN card designer), **Update Menumate** (`Members.csv` patron import), **Update Brevo mailing list** (syncs lists through the Brevo API) and **MYOB contacts** (`MYOB-Contacts.txt`).

- MYOB contacts was built from MYOB's published import layout, not a sample file: tab-separated, `{}` in A1, headings on row 2. MYOB Business (Essentials) and AccountRight use the same layout. Check the first import maps the columns as expected, and whether MYOB wants UTF-8 or ANSI for names with macrons.
- **Waiting on a sample:** an export of a few contacts has been requested from the treasurer. When it arrives, put it in `samples/myob/` and compare it column by column. Check which product it came from: the original MYOB Essentials (not yet moved to MYOB Business) may need a different file, and if AccountRight differs (field lengths, the `{}` line), add a second download button for it.

- Brevo was tested against a stand-in, not the real API. Do the first real run against test lists.
- Brevo contacts only get FIRSTNAME and LASTNAME. Membership number or expiry could be sent too, once matching contact attributes exist in Brevo.

## Menumate import columns

Worked out by decoding a copy of Menumate's database against past imports (`Import\Old\members_*.csv`). Menumate matches members by `MEMBER_NUMBER`, so re-importing updates rather than duplicates.

| Col | Export value | Menumate field | |
|---|---|---|---|
| 1 | `2` | `CONTACTS.CONTACT_TYPE` (2 = member) | confirmed |
| 2 | preferred name, else first name | `CONTACTS.NAME` (shown at the till) | confirmed |
| 3 | phone, if not a mobile | `CONTACTS.PHONE` | from field order |
| 4 | phone, if a mobile (02…) | `CONTACTS.MOBILE` | from field order |
| 7 | company | `CONTACTS.MAILING_ADDRESS` | confirmed |
| 10 | `1315` + number | `CONTACTCARDS.SWIPE_CARD` (what the card's QR code scans as) | confirmed |
| 11 | `+` | not stored (probably "add/update") | unknown |
| 12 | `0` | `CONTACTS.TOTAL_SPENT` | confirmed value, field by order |
| 13 | membership number | `CONTACTS.MEMBER_NUMBER` | confirmed |
| 14 | `-1` | `CONTACTS.SITE_ID` | confirmed |
| 15 | first name | `CONTACTS.FIRST_NAME` | confirmed |
| 17 | surname | `CONTACTS.LAST_NAME` | confirmed |
| 21 | date of birth, dd/mm/yyyy | `CONTACTS.DATEOFBIRTH` | from field order |
| 36–40 | `Y,Y,N,N,N` | not stored on the member record | unknown |

Likely but unused: 5 `PHONE_EXTENSION`, 6 `EMAIL`, 8 `INITIALS`, 9 `PIN`, 16 `MIDDLE_NAME`, 18 `SEX`, 19 `TAB_ENALBED`, 20 `PAYROLL_ID`, 22 `KNOWN_AS`.

### For investigation: subscription expiry

Menumate stores an expiry in `MEMBERSHIP_SUBS_DETAILS.SUBS_EXPIRY_DATE` (one row per member, linked by `CONTACTS_KEY`), but it's empty for every member and the CSV importer's insert doesn't include it, so `Members.csv` can't set it as it stands. Plan: set an expiry on one member by hand in Menumate, take a copy of `MENUMATE.FDB`, and see what changed: whether expiry is used at the till, and whether another column or route could set it.

The same check could show what the other subscription fields do: every imported member has `SUBS_TYPE` `AUTO`, `SUBS_PAID` `T`, paid amount 0, receipt `-` and `POINTS_RULES_SUBS` 192 (binary `11000000`). That may come from the `Y,Y,N,N,N` in columns 36–40 (two Y's, two bits set) — unproven.

To confirm the "from field order" columns, import one test member with a marker in each empty column (`C03`, `C04`, …) into a test copy of Menumate and check where each landed. Check the date of birth shows correctly after the first real import.

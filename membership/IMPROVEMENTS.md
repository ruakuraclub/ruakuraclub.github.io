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

**Print cards** (`RuakuraClub.xlsx` for the POINTMAN card designer) and **Update Menumate** (`Members.csv` patron import) are done. The remaining placeholder needs its format before it can be built:

- **Update Brevo mailing list**: the contact attribute names and lists used.

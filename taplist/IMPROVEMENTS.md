# Taplist – future improvements

## Automatic keg consumption from Menumate

Today keg consumption comes from running a saved SQL query against the Menumate database by hand. Idea: a scheduled script on the till runs that query and sends the results to the website, so keg levels (the "amount left" indicators) update on their own.

Status: idea only. Next step: share the saved SQL (tables and columns used) and sketch the script and the receiving endpoint.

### Shape

```
Scheduled task on the till (e.g. every 15 min, or nightly after close)
  → PowerShell script
      → connects to the running Firebird server on localhost (read-only)
      → runs the saved keg SQL for "since last run"
      → POSTs the rows as JSON over HTTPS
  → PHP endpoint on the website (API token)
      → stores them in MariaDB (upsert, so re-sends are harmless)
```

### On the till (Windows 10 embedded)

- **Connect through the Firebird server, never open the `.FDB` file directly.** Menumate runs a Firebird 2.5 server that already has `MENUMATE.FDB` open. Connect as a client to `localhost:3050`. Opening the file another way risks locking or corrupting it. Use read-only transactions and never write.
- **No installs needed:** `FirebirdSql.Data.FirebirdClient.dll` is already in the MenuMate folder (the .NET Firebird driver), so a PowerShell 5.1 script (built into Windows 10) can load it. Alternative: `isql` from the Firebird installer in `MenuMate\Firebird` can run a `.sql` file and write CSV.
- **Credentials:** Menumate installs often use the default `SYSDBA` / `masterkey`. Prefer a separate read-only Firebird user with `SELECT` on just the tables the query needs. Keep its password out of the script (e.g. Windows Credential Manager).
- **Scheduling:** Windows Task Scheduler, run as a low-privilege local account, "run whether user is logged on or not", retry on failure.
- **Old-Windows HTTPS:** PowerShell 5.1 may need `[Net.ServicePointManager]::SecurityProtocol = 'Tls12'` before calling a modern HTTPS endpoint.
- **Keep it light:** query only what's new since the last run (`TIME_STAMP > last sent`), or run nightly after close. Keep a "last sent" marker locally so an outage just means the next run catches up.

### Query detail

Menumate keeps the current trading day in the `DAY…` tables (`DAYARCBILL`, `DAYARCHIVE`, …) and moves them to `ARCBILL` / `ARCHIVE` at end of day (Z report). Its own reports `UNION ALL` the two. A frequent sync must query both, filtered by time, or it will miss either today or history.

### Receiving side (PHP + MariaDB on the website hosting)

- Small endpoint authenticated by a long random token in a request header (never in the URL), HTTPS only, accepting only the expected JSON shape.
- Idempotent: key rows on something stable (e.g. invoice number + item + time) and upsert, so retries and overlapping windows never double-count.
- Push from the till rather than pull: no ports opened to the till.

### Caveats

- Reading the database is generally fine, but check it doesn't affect the Menumate support arrangement. This design never writes to it.
- Menumate upgrades can rename tables or columns: the script should log errors and alert, not silently send nothing.
- Keep the till's clock synced; everything keys on timestamps.

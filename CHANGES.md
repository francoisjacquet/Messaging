# CHANGES
## Messaging Module

Changes in 1.5
--------------
- Attach File to Messages (Premium)

Changes in 1.4
--------------
- Add Arabic translation, thanks to Ali Al-Hassan in locale/ar_AE.utf8/
- Translate "Recipients" in Messages.fnc.php

Changes in 1.3
--------------
- Exclude self from recipients when Teacher writing message to Teachers in Write.fnc.php
- Remove modfunc from URL using new RedirectURL function in Messages.php

Changes in 1.2
--------------
- SQL error fix on install: check if exists before INSERT in install.sql

Changes in 1.1
--------------
- Use TinyMCE as Message editor in Write.php
- Fix: No link for name column in Write.fnc.php
- Fix: change school while viewing message in Messages.php
- Update screenshots
- Add GetRecipientsHeader(), display current tab in bold in Write.fnc.php
- Display current tab in bold in Messages.php

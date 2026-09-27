RMS SETTINGS UI V2

Purpose:
- Increase typography so the Settings page is balanced with the dashboard.
- Improve spacing, input hierarchy, buttons, and responsive behavior.
- Add a dedicated Connection Logs container for the future OpenAI test action.

FILES
1. dashboard-settings-v2.php
   Full dashboard source with the OpenAI Settings section upgraded.

2. resources/css/dashboard/settings-v2.css
   Complete Settings V2 CSS.

3. resources/js/dashboard/settings-v2.js
   Test Connection visual loading state only.
   It DOES NOT fake a successful OpenAI connection.

INSTALL (recommended)
A. If your current dashboard contains the Settings workspace directly:
   Replace the current dashboard source with dashboard-settings-v2.php.

B. Import the CSS in resources/css/app.css:
   @import "./dashboard/settings-v2.css";

C. Import the JS in resources/js/app.js:
   import "./dashboard/settings-v2.js";

D. Run:
   php artisan optimize:clear
   npm.cmd run dev

E. Hard refresh:
   Ctrl + F5

IMPORTANT
The Connection Logs UI is ready, but actual OpenAI connection testing is not
implemented in this UI package. When backend integration is added, the logs
should be populated from the real response/error instead of simulated data.

# Device Blocker Lite

Early device-based visitor blocking for WordPress using **UA-CH device model** and **User-Agent patterns**.

---

## Overview

Device Blocker Lite is a lightweight WordPress plugin designed to restrict access for specific device profiles at a very early stage of page load, **before any visible content is rendered**.

The plugin is intended to be used as a **manual technical workaround** after suspicious or unwanted traffic patterns have already been identified through external analysis tools.

---

## Workaround-Oriented Design

This plugin is **not an automated fraud detection system**.

Instead, it operates as a **post-analysis mitigation tool**, where blocking rules are intentionally defined by the site operator based on prior investigation.

### Typical investigation sources include:

- Web analytics platforms (e.g. Google Analytics)
- Google Ads / AdWords traffic reports
- Server logs
- Manual pattern analysis

Once specific device models or User-Agent patterns are identified as problematic, this plugin can be used to reduce exposure by preventing those visitors from fully accessing the site.

---

## Relation to Fraud & Advertising Traffic

Device Blocker Lite **may help mitigate** certain traffic irregularities or fraudulent patterns, but it **does not guarantee prevention** and **does not claim full coverage**.

### Important clarifications

- The plugin does **not** detect fraud
- The plugin does **not** analyze clicks or user behavior
- The plugin does **not** interfere with advertising platforms
- All decisions are manual and rule-based

In workflows involving **Google Ads (AdWords)**, this plugin can serve as a **technical workaround layer**, applied **after abnormal traffic patterns have been observed and verified externally**.

Results are approximate by nature and fully dependent on the accuracy of the identified patterns.

---

## What the Plugin Does

- Blocks access based on:
  - Device model (via User-Agent Client Hints)
  - User-Agent string patterns
- Executes before page content is rendered
- Prevents page visibility for blocked profiles
- Programmatically navigates the browser back to the previous page or referrer for blocked visitors
- Operates with minimal performance overhead

---

## What the Plugin Does NOT Do

- ❌ No automated fraud detection  
- ❌ No traffic classification  
- ❌ No click analysis  
- ❌ No behavioral tracking  
- ❌ No content modification or cloaking  
- ❌ No guarantees regarding advertising metrics  

---

## How It Works (Technical Summary)

1. Page rendering is temporarily hidden on initial load.
2. Device model is obtained using **UA-CH** when available.
3. User-Agent patterns are evaluated via a **REST endpoint**.
4. If a rule matches:
   - The page is never rendered.
   - The browser is programmatically navigated back to the previous page or referrer as early as possible.
   - This typically results in a very short session duration and may increase the likelihood of invalid interactions (no guarantees).
5. If no rule matches:
   - The page is displayed normally.

---

## Use Cases

- Manual mitigation of previously identified suspicious traffic
- Device-based access control
- Early blocking of unwanted visitor profiles
- Reducing unnecessary page interactions

This plugin is especially useful when:

- Traffic analysis has already been performed
- Immediate action is needed
- Automated solutions are unavailable or delayed

---

## Installation

1. Download or clone this repository.
2. Upload it to `/wp-content/plugins/`.
3. Activate **Device Blocker Lite** in the WordPress admin panel.

---

## Configuration

After activation, administrators can configure:

- Blocked device models
- User-Agent blacklist patterns
- Redirect / return destination behavior for blocked visitors

All rules are explicitly managed by the site owner.

---

## Privacy & Data Handling

- No personal data is collected
- No tracking or fingerprinting
- No third-party requests
- No storage of visitor information

---

## Limitations

- Blocking is approximate and pattern-based
- Coverage depends entirely on rule quality
- New or unknown patterns will not be affected
- Effectiveness varies by traffic source and environment
- No outcomes are guaranteed

---

## License

MIT License

Copyright © MHKEY

---

## Disclaimer

This software provides a **manual, rule-based technical workaround**.

It does **not** replace analytics, investigation, or platform-level protection systems and should **not** be presented as a definitive solution for fraud prevention or advertising performance enforcement.

---

## Author

**MHKEY**

GitHub: https://github.com/MHKEY81

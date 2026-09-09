## 2025-05-18 - Admin Dashboard Icon-Only Buttons Accessibility
**Learning:** Icon-only trigger buttons (such as navbar theme switchers, mobile menu toggles, sidebar collapse buttons, and DataTables action dropdown triggers) in Blade templates lack accessible names unless explicit `aria-label` or `aria-labelledby` attributes are supplied.
**Action:** Whenever creating or editing icon-only interactive elements in Blade views, always add a descriptive `aria-label` and ensure image triggers have non-empty `alt` text.

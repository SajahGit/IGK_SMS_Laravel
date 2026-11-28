# Bug Fix Report

No Stage 4 bug fixes have been executed.


---

## 2026-11-28 Bug Fixes Verified
1. **Admin Learning Materials Pagination & Light-Mode UI**: Switched `->links()` to `pagination::bootstrap-5`, replaced `table-dark`/`btn-outline-light` with theme-adaptive classes, and expanded Bulk Access columns in `Admin/LearningMaterialsIndex.blade.php`.
2. **Notification Dropdown Clipping & Detail View**: Anchored `.topbar .notification-dropdown-menu` in `public/css/glass.css`, added `/notifications` and `/notifications/{id}` views, and resolved `Open Related Page` URLs on the active host (`NotificationController::resolveActionUrl()`).
3. **Download Restriction & Expiry Preservation**: Enforced `allow_download = false` consistently in `LearningMaterialService::canDownload()`, replaced native `<video controls>` with custom controls when restricted, and verified expired materials preserve their files on disk.
4. **Cafeteria Cross-Branch Null Inventory**: Bypassed `BranchOwned` on eager-loaded `inventoryItem`, `student`, and `cafeteriaItem` in `CafeteriaController@index` and added null guards in `CafeteriaIndex.blade.php`.
5. **Student Invoice Summary & Guardian Notifications**: Excluded `cancelled`/`draft` invoices in `InstitutionAdmin\StudentController@show` and added guardian notifications to `SendInvoiceIssuedNotification` and `SendPaymentReceivedNotification`.

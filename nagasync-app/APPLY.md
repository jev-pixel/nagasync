# Document Requests v4: barangay + City Hall documents

Copy the files into `D:\Xampp\htdocs\nagasync-platform\nagasync-app`, keeping the same relative paths.
Already applied earlier: migrations 000001-000003. Delete the two empty stubs from `make:model -m`
(`2026_10_28_124030_...` and `2026_10_28_124040_...`) if you have not yet.

## Replace (full files)
- routes/web.php
- app/Models/DocumentRequest.php
- app/Models/DocumentRequirement.php
- app/Models/RequestAttachment.php
- app/Http/Controllers/Citizen/DocumentController.php
- app/Http/Controllers/AttachmentController.php
- app/Http/Controllers/ReviewAccess.php
- app/Http/Controllers/Staff/RequirementReviewController.php
- database/seeders/DocumentRequirementSeeder.php
- resources/js/Pages/Citizen/Documents/Index.vue
- resources/js/Pages/Staff/Documents/Review.vue
- tests/Feature/DocumentRequirementsTest.php

## New
- database/migrations/2026_10_28_000004_add_city_level_and_linked_requirements.php

## Commands
```
php artisan migrate
php artisan db:seed --class=DocumentRequirementSeeder
php artisan test --filter=DocumentRequirementsTest
npm run dev
```

## Must check before it works end to end
1. `city_staff` role: the staff routes now allow `role:barangay_staff,city_staff,admin`.
   Check how roles are stored (`SHOW COLUMNS FROM users LIKE 'role';`). If `role` is an ENUM,
   add `city_staff` to it; if you keep roles in a table or config list, add it there.
   Until then, admin can review City Hall requests.
2. Your existing staff pages (`Staff/DocumentReviewController` + `Staff/Documents/Index.vue`) have
   not been updated yet. They need to: list city-level requests for city_staff/admin, link to
   `staff.documents.review`, and show `errors.status` from the approval gate.
3. The seeded City Hall requirements are starter values. Confirm them with the city's offices.
4. Existing rows: `level` defaults to `barangay`, so current data is unaffected.

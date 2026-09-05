---
paths:
  - app/Http/Controllers/CaregiverBookingController.php
---

# Controllers

## Serialize caregiver slot booking with Cache::lock
BookingService::createRequest must run its overlap re-check and insert under Cache::lock('booking:caregiver:{caregiver_id}', 15). Same for cancel (booking:cancel:{id}), replacement (booking:replacement:{id}), schedule store (schedule:{id}), and payout request (payout:request:{id}). Never do a check-then-insert/update without the lock or an atomic where(status)->update() guard.

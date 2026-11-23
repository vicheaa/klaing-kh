---
paths:
  - 'app/Domain/**'
---

# Domain

## Keep market data in domain modules
Organize market data by domain and keep persistence in Eloquent models. Put writes in actions and reusable read queries in services shared by Inertia and the API. Pass validated source data as typed DTOs and inject collaborators through constructors. Keep effective, upstream publication, and last-successful-sync times distinct. Store timestamps in UTC and use `Asia/Phnom_Penh` for local publication times and display.

## Testing

[Sep 13, 2026]

- [ ] Archiving is a new feature added on Burial Record; To view archived records, use the filter button at the table
- [ ] User account termination is a new feature; Check if the session is currently logged in and is terminated by the admin, does it end or continue
- [ ] Remove age column on the DB; Auto computed age is a new feature check that when creating new record (there are 3 ways to do this)
    - Jonas' reminder, there are two way we are computing the age, one for backend used for (admin dashboard and admin burial record show),
    - The other one is frontend, we are primarily using it for (clerk burial record whole CRUD)
- [ ] Check if the "See Lot Image" feature is working correctly

- [ ] Try to create lot manually, then import the records on the Admin side;
      See if the records will automatically assign to those new lots
- [ ] Cross reference to the actual record; The pending filter is responsible for showing record that have no "Date of depository" check on the actual data of Panteon if this is accurate.
- [ ] Clarify if we should make "Precinct number" required on Clerk/BurialRecord/CreateView
- [ ] Remember that there are three ways to create new burial record; Seeding, Manual Creation by Clerk, and Admin Importing
- [ ] Make sure that every action happening is being added on Activity Logs

### Add-Ons (Must have)

- Registration "user agreement"

### Issues

- [ ] Do we still add image on Modal of Visitor searched burial record modal?

### Docs Revisions

- Low fidelity
    - Generate Report (w new changes)
    - Invite Clerk
    - Activity Log
    - Database backup
    - Lot Man
    - Burial Record
    - Certificate of Service Generator Form
    - Certificate of Service Upload

    - Account Management
    - Change Password
    - Forgot password

- Proposed Flowchart
- ERD

- Read the scope and limitation -> Formulate questions for sir Ketch to clarify -> Then revise the scope and limitation "Single source of truth" on the paper remove that

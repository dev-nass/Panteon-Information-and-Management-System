## Testing

- [ ] Try to create lot manually, then import the records on the Admin side;
      See if the records will automatically assign to those new lots
- [ ] Cross reference to the actual record; The pending filter is responsible for showing record that have no "Date of depository" check on the actual data of Panteon if this is accurate.
- [ ] Clarify if we should make "Precinct number" required on Clerk/BurialRecord/CreateView
- [ ] Remember that there are three ways to create new burial record; Seeding, Manual Creation by Clerk, and Admin Importing
- [ ] Make sure that every action happening is being added on Activity Logs

### Add-Ons (Must have)

- Registration "user agreement"

### Issues

- [x] Org chart should be included on the user side UI
- [ ] Archive Burial Records, no complete deletion
      On confirmation modal include the reason archive action is being done "pull out", "transfer" etc...
- [ ] User Management account "termination" instead of "delete"
      Do we create new table for archiving of burial record and terminating of users (clerk)?
- [ ] Remove the "age" on the deceased record, but its a computed state on the frontend
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

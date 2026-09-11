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

- [ ] Backup now is not working
- [ ] Certificate of Service
    - Consult Panteon for the actual template
    - if they rejected, use ai to forcefully copy the template
- [ ] Org chart should be included on the user side UI

- [ ] Archive Burial Records, no complete deletion
      On confirmation modal include the reason archive action is being done "pull out", "transfer" etc...
- [ ] User Management account "termination" instead of "delete"
      Do we create new table for archiving of burial record and terminating of users (clerk)?
- [ ] Remove the "age" on the deceased record, but its a computed state on the frontend

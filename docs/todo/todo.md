## No plans

- [ ] Burial Schedule is bloated; Implement a way for them so each day on
      "Monthly" view is limited, and show more of the deceased records on the "today".
- [ ] Proper implementation of Certificate of Service;
      **Meaning they could upload the .docs file of the COS** and they can
      select which template they want to use and then fill out the form and
      it's finished.
- [ ] The Clerk invitation email is not styled yet

## With Plans

- [ ] CSV export on user management

## Testing

- [ ] Try to create lot manually, then import the records on the Admin side;
      See if the records will automatically assign to those new lots

## Sir Ketch Suggestions

### Minor Changes

- [x] Dashboard
    - Graph should be unified;
    - Clusters tab should be more clear
    - Numbers should be separated by comma
- [x] Generate Report
    - Monthly, Weekly and Annual Report
    - Add Annual Report
    - Footer dapat yung "This report is system generated"
    - The name of who generated the report is not updated
- [x] User Management
    - Deleting should have confirmation modal
- [x] Burial Schedule
    - Calendar view should reduce the bloated view;
    - Use "View more record..." on calendar
- [x] Adjust the operational dashboard
    - On clerk dashboard double check the UI
- Double check the functionality of change password
- Changes on the Login, redundant ang "Panteon De Dasma" na text

### Most Priority

- Certificate of Service
    - Should be .docs where they can freely change; No hard
      coding the design.
- Separate page for log/activity reporting
    - Database Backup (.sql) schema
    - Proper filtering showing all logs, not just
      per page.
- Database backup process
- Analytics
- Account activation for admin side
- UI admin dashboards

### Add-Ons (Must have)

- Registration "user agrement"
- Proper rate limit specially on API end points

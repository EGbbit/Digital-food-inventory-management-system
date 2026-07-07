# Appendix: Test Evidence Register

This appendix stores screenshots and command-output artifacts referenced in Chapter 5.

## Evidence Storage Location
- docs/evidence

## Naming Rule
- Use pattern AppendixId_TestCaseId_short-description.png
- Example names:
	- A1_TC01_login_success.png
	- A2_TC02_invalid_password.png
	- A10_TC10_order_success.png

## Master Status Overview
| Appendix ID | Test Case | Execution Status | Evidence File Name |
|---|---|---|---|
| A0 | Automated Verification | Executed | A0_php_lint_pass.png |
| A1 | TC-01 | Executed | A1_TC01_login_success.png |
| A2 | TC-02 | Executed | A2_TC02_invalid_password.png |
| A3 | TC-03 | Executed | A3_TC03_cross_role_access_denied.png |
| A4 | TC-04 | Pending | A4_TC04_change_password.png |
| A5 | TC-05 | Pending | A5_TC05_admin_create_user.png |
| A6 | TC-06 | Pending | A6_TC06_admin_update_reset.png |
| A7 | TC-07 | Pending | A7_TC07_delete_constraint.png |
| A8 | TC-08 | Pending | A8_TC08_ingredient_add_status.png |
| A9 | TC-09 | Pending | A9_TC09_stock_movement.png |
| A10 | TC-10 | Executed | A10_TC10_order_success.png |
| A11 | TC-11 | Executed | A11_TC11_unavailable_item_block.png |
| A12 | TC-12 | Pending | A12_TC12_open_menu_filter.png |
| A13 | TC-13 | Pending | A13_TC13_chef_inventory_usage_wastage.png |
| A14 | TC-14 | Pending | A14_TC14_chef_stock_note_submission.png |
| A15 | TC-15 | Pending | A15_TC15_threshold_update.png |
| A16 | TC-16 | Pending | A16_TC16_menu_add_update.png |
| A17 | TC-17 | Executed | A17_TC17_predictive_report_generated.png |
| A18 | TC-18 | Pending | A18_TC18_csv_export_download.png |
| A19 | TC-19 | Pending | A19_TC19_system_audit_visibility.png |
| A20 | TC-20 | Pending | A20_TC20_chef_note_ack_reopen.png |

## Detailed Appendix Records

### Step-by-Step Evidence Format (Must Match Section 5.3.4)
For each appendix item, use this execution matrix directly under the summary table:

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open target module | Module loads | Update during execution | Pass or Fail |
| 2 | Enter test data | Input accepted or validated | Update during execution | Pass or Fail |
| 3 | Execute action | Correct response appears | Update during execution | Pass or Fail |
| 4 | Verify output state | Data/view reflects expected logic | Update during execution | Pass or Fail |

Record final evidence line after matrix:
- Overall Result: Pass or Fail
- Screenshot File Name
- Appendix ID

### A0: Automated Verification Evidence
| Field | Entry |
|---|---|
| Appendix ID | A0 |
| Linked Test Case | Automated verification run |
| Type of Test | Verification |
| Functionality Tested | PHP source syntax validity |
| Tool Used | PHP CLI |
| Command | php -l executed across all project PHP files |
| Expected Result | No syntax errors detected |
| Actual Result | No syntax errors detected in all scanned files |
| Pass/Fail | Pass |
| Screenshot File Name | A0_php_lint_pass.png |
| Screenshot Focus | Terminal output showing success lines |

### A1: Test Case 1 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A1 |
| Linked Test Case | TC-01 |
| Type of Test | Functional Validation |
| Functionality Tested | Waiter login and redirect |
| Test Data | waiter@gmail.com and valid password |
| Expected Result | Redirect to waiter dashboard |
| Actual Result | Redirect to waiter dashboard confirmed |
| Pass/Fail | Pass |
| Screenshot File Name | A1_TC01_login_success.png |
| Screenshot Focus | Waiter dashboard header and logged-in identity |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open login page | Login form displays | Login form displayed | Pass |
| 2 | Enter waiter credentials | Credentials accepted for submission | Credentials entered successfully | Pass |
| 3 | Click Login | Redirect to waiter dashboard | Redirect occurred | Pass |
| 4 | Verify dashboard identity | Waiter dashboard and user name visible | Dashboard header and waiter identity visible | Pass |

Overall Result: Pass

### A2: Test Case 2 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A2 |
| Linked Test Case | TC-02 |
| Type of Test | Negative Functional |
| Functionality Tested | Invalid password handling |
| Test Data | admin@gmail.com with incorrect password |
| Expected Result | Login denied with error message |
| Actual Result | Invalid password message displayed |
| Pass/Fail | Pass |
| Screenshot File Name | A2_TC02_invalid_password.png |
| Screenshot Focus | Login page with visible invalid password error |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open login page | Login form displays | Login form displayed | Pass |
| 2 | Enter valid email and wrong password | Submission allowed | Input accepted | Pass |
| 3 | Click Login | Authentication fails | Authentication failed | Pass |
| 4 | Verify error feedback | Invalid password message shown | Invalid password message shown | Pass |

Overall Result: Pass

### A3: Test Case 3 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A3 |
| Linked Test Case | TC-03 |
| Type of Test | Security Authorization |
| Functionality Tested | Manager blocked from admin-only module |
| Test Data | Manager session plus direct admin URL |
| Expected Result | Access denied and redirected |
| Actual Result | Redirected away from admin resource |
| Pass/Fail | Pass |
| Screenshot File Name | A3_TC03_cross_role_access_denied.png |
| Screenshot Focus | Redirected login page or denied access outcome |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Login as manager | Manager session created | Manager session active | Pass |
| 2 | Navigate to admin-only URL | Access block triggered | Redirect triggered | Pass |
| 3 | Verify final page | User is not granted admin page access | Admin page inaccessible | Pass |
| 4 | Confirm security behavior | Role boundary enforced | Role boundary enforced | Pass |

Overall Result: Pass

### A4 to A9: Pending Evidence Records
| Appendix ID | Linked Test Case | Required Screenshot Focus | Planned File Name |
|---|---|---|---|
| A4 | TC-04 | Change password success or validation error state | A4_TC04_change_password.png |
| A5 | TC-05 | User creation success message in manage users | A5_TC05_admin_create_user.png |
| A6 | TC-06 | Updated user row and reset status message | A6_TC06_admin_update_reset.png |
| A7 | TC-07 | Delete prevention or constraint error message | A7_TC07_delete_constraint.png |
| A8 | TC-08 | Added ingredient row with computed stock status | A8_TC08_ingredient_add_status.png |
| A9 | TC-09 | Stock movement result and updated movement table row | A9_TC09_stock_movement.png |

### A10: Test Case 10 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A10 |
| Linked Test Case | TC-10 |
| Type of Test | End-to-End Functional |
| Functionality Tested | Waiter order creation |
| Test Data | Table T21, African Tea, quantity 2 |
| Expected Result | Order created and appears in recent orders |
| Actual Result | Order success message and pending row confirmed |
| Pass/Fail | Pass |
| Screenshot File Name | A10_TC10_order_success.png |
| Screenshot Focus | Success message and newly created order row |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open waiter orders page | Form and recent orders table visible | Page loaded correctly | Pass |
| 2 | Enter table, item, quantity | Input accepted | Input accepted | Pass |
| 3 | Submit Create Order | Success message and order number generated | Success message displayed with order number | Pass |
| 4 | Verify recent orders row | New pending order appears | New pending order row visible | Pass |

Overall Result: Pass

### A11: Test Case 11 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A11 |
| Linked Test Case | TC-11 |
| Type of Test | Negative Functional |
| Functionality Tested | Unavailable item block |
| Test Data | NonExisting Item XYZ |
| Expected Result | Order blocked and warning shown |
| Actual Result | Alert shown and order blocked |
| Pass/Fail | Pass |
| Screenshot File Name | A11_TC11_unavailable_item_block.png |
| Screenshot Focus | Unavailable item warning or alert state |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open waiter orders form | Form available for entry | Form loaded | Pass |
| 2 | Enter non-existing menu item | Input accepted | Input accepted | Pass |
| 3 | Submit order | Order creation blocked | Alert displayed and submission blocked | Pass |
| 4 | Verify behavior | No invalid order created | No invalid order row created | Pass |

Overall Result: Pass

### A12 to A16: Pending Evidence Records
| Appendix ID | Linked Test Case | Required Screenshot Focus | Planned File Name |
|---|---|---|---|
| A12 | TC-12 | Filtered menu list after query and category filter | A12_TC12_open_menu_filter.png |
| A13 | TC-13 | Chef inventory usage or wastage result message | A13_TC13_chef_inventory_usage_wastage.png |
| A14 | TC-14 | Chef stock note submission and feed visibility | A14_TC14_chef_stock_note_submission.png |
| A15 | TC-15 | Threshold save action and updated value | A15_TC15_threshold_update.png |
| A16 | TC-16 | Menu add or update success in manager controls | A16_TC16_menu_add_update.png |

### A17: Test Case 17 Evidence
| Field | Entry |
|---|---|
| Appendix ID | A17 |
| Linked Test Case | TC-17 |
| Type of Test | Functional Analytics |
| Functionality Tested | Predictive report generation |
| Test Data | Current month data |
| Expected Result | Report generation success confirmation |
| Actual Result | Predictive report generated for July 2026 message displayed |
| Pass/Fail | Pass |
| Screenshot File Name | A17_TC17_predictive_report_generated.png |
| Screenshot Focus | Generate report confirmation and report panel |

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open manager controls page | Predictive report panel visible | Panel visible | Pass |
| 2 | Click Generate Report | Report generation process runs | Action executed | Pass |
| 3 | Verify success feedback | Confirmation message displayed | July 2026 generation message displayed | Pass |
| 4 | Verify report content panel | Report body visible/updated | Report panel visible with generated content | Pass |

Overall Result: Pass

### A18 to A20: Pending Evidence Records
| Appendix ID | Linked Test Case | Required Screenshot Focus | Planned File Name |
|---|---|---|---|
| A18 | TC-18 | Download confirmation and exported CSV file list | A18_TC18_csv_export_download.png |
| A19 | TC-19 | System audit table with user and activity records | A19_TC19_system_audit_visibility.png |
| A20 | TC-20 | Chef-note status changed to acknowledged or reopened | A20_TC20_chef_note_ack_reopen.png |

## Accuracy Notes for Assessment
- Some seeded account passwords may have been changed during prior use of the system.
- Therefore, login testing should always include one positive credential check and one negative credential check.
- Chapter 5 should remain screenshot-free; only appendix IDs are referenced in chapter narrative.

## Pending Appendices Update Rule
For A4 to A9, A12 to A16, and A18 to A20, complete the same step matrix format after execution and then update:
- Execution Status in Master Status Overview
- Actual Result in each appendix record
- Overall Result line

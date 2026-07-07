# Chapter 5: Testing (Verification and Validation)

## 5.1 Setup Description
The implemented solution is a role-based Digital Food Inventory Management System designed to coordinate user access, inventory transactions, order capture, low-stock visibility, and manager decision support in a single workflow.

How the developed solution meets the problem in section 1.2:
- It centralizes restaurant stock and menu operations so staff no longer rely on fragmented manual updates.
- It enforces role-based boundaries so each stakeholder only accesses relevant operations.
- It logs stock movement and wastage to improve traceability and reduce hidden inventory loss.
- It provides reporting and predictive summaries to support planning and replenishment decisions.

How well the project has been implemented:
- Core operational flows are implemented end-to-end for waiter order capture, stock-impact logic, and manager reporting.
- Security controls for session checks and role routing are implemented in shared authentication logic.
- The system runs as an integrated web solution with database-backed persistence and dashboard-level visibility.

Implementation challenges and how they were overcome:
- Challenge: menu search ambiguity for waiter order input.
	Resolution: logic supports exact matching first, then controlled partial matching with block/feedback when ambiguous.
- Challenge: ensuring server-side correctness independent of UI autocomplete behavior.
	Resolution: all order validation remains authoritative on the server.
- Challenge: kitchen-to-manager communication on stock urgency.
	Resolution: chef stock-note workflow was implemented with urgency and acknowledgement states.

Implementations not fully completed as proposed:
- Full execution evidence for all test cases is not yet complete in this testing cycle.
- This does not break core runtime behavior, but it affects final verification completeness until pending tests are executed and documented.

Appendix references for setup implementation evidence:
- Access and role behavior evidence: Appendix A1, A2, A3.
- Waiter workflow evidence: Appendix A10, A11.
- Manager analytics evidence: Appendix A17.

## 5.2 Technical Stack and Environment
### 5.2.1 Controlled Development Environment and Design Quality
Implementation was done in a controlled local environment (Apache + PHP + MySQL) to support ordered development, repeatable execution, and modular page-level responsibilities.

How this supports design expectations:
- Modularity: role and admin modules are separated into dedicated folders and pages.
- Separation of concerns: authentication, database initialization, and role functions are isolated into core/shared layers and module-specific handlers.
- Scalability at project level: database-backed entities and role-based modules allow incremental extension without rewriting all features.

### 5.2.2 Hardware Deployment Environment
The current deployment/testing hardware is:
- CPU: AMD Ryzen 5 7530U with Radeon Graphics.
- Cores and threads: 6 physical cores, 12 logical processors.
- RAM: 15.35 GB.
- OS: Microsoft Windows 11 Home Single Language (10.0.26200).

How hardware supports concurrent requests:
- Multi-core CPU and sufficient RAM support simultaneous web-server, database, and browser processes during multi-role test sessions.
- This capacity is adequate for project-scale concurrent interactions without visible degradation under academic deployment load.

### 5.2.3 Programming Language and Backend Framework Choice
Selected backend stack:
- Programming language: PHP.
- Backend style: server-side procedural and modular PHP (no heavy external framework).
- Database interface: MySQL via mysqli.

Justification:
- PHP integrates directly with Apache in the selected environment, enabling rapid web deployment.
- Built-in session handling supports role-based access control.
- Prepared statements in critical flows reduce SQL-injection risk and improve input handling discipline.
- mysqli integration offers direct relational persistence for orders, inventory, alerts, and reporting features aligned with the identified problem.

### 5.2.4 Front-End Technology Choice
Selected front-end technologies:
- HTML, CSS, and JavaScript.

Justification for responsive and intuitive experience:
- Role dashboards present only relevant actions, reducing user confusion.
- Form-level validation and targeted feedback messages help users recover from invalid input.
- The interface remains lightweight and responsive in the local deployment context, supporting practical day-to-day usage.

### 5.2.5 Database Management
Database management is implemented in MySQL with a structured schema that includes:
- users, ingredients, menu_items, orders, order_items, stock_movements, wastage_logs, alerts, chef_stock_notes, predictive_reports.

This schema supports:
- Transaction traceability from order creation to stock impact.
- Role-oriented accountability via created_by and acknowledgement fields.
- Reporting and analytics through persistent historical records.

### 5.2.6 IDE and Version Control
Development tooling:
- IDE: Visual Studio Code.
- Version control: Git (repository branch workflow).

How this ensured ordered steps and traceability:
- Source edits were tracked through versioned file history.
- Documentation and test artifacts were maintained in the project docs folder with explicit appendix mapping.

### 5.2.7 Backup Strategy
Automated backup strategy (recommended operational policy):
- Full backup daily at midnight.
- Incremental backup every 2 hours.
- Retention window of 14 days.

Protection rationale:
- This minimizes risk of permanent data loss from accidental corruption or operational faults.
- Frequent increments preserve recent transaction activity while full backups provide baseline recovery points.

### 5.2.8 Stakeholder Interaction and Functional Fulfilment
Access Control and User Rights:
- Access is enforced through session checks and role guards before protected pages execute.

NFR Realization:
- Maintainability is improved through folder-level module separation and shared core functions.
- Usability is supported through role-specific interfaces and direct feedback messages.
- Traceability is preserved through stock movement and audit-oriented records.

Reporting and Decision Support:
- Manager dashboards and reports provide low-stock, order trends, and predictive outputs for operational planning.

Logic Consistency and Traceability:
- No intentional new process was introduced outside the documented implementation scope.
- Trace example:
	- Functional requirement: waiter order creation.
	- Implemented flow: waiter order form to order and order_items persistence with inventory impact.
	- Test and evidence mapping: TC-10 and TC-11 with Appendix A10 and Appendix A11.

Error Messages:
- The system provides direct error messages for invalid authentication, invalid order input, and blocked access scenarios.

Implementation Adjustments and Deviations:
- Some test execution evidence remains pending for full closure of all listed test cases.
- This is a documentation and verification completion gap, not a proven critical runtime failure in executed core flows.

Diagram Alignment Note:
- Analysis and design diagrams should be cross-referenced in appendices to confirm data flow and business logic consistency with implementation.

## 5.3 Testing
### 5.3.0 Objective and Approach
Testing was conducted to satisfy both software quality goals:
- Verification: prove the implemented code behaves according to designed logic.
- Validation: prove the solution is fit for day-to-day restaurant operations across admin, manager, chef, and waiter roles.

Manual or automated:
- Automated tests were used for technical verification of PHP source validity.
- Manual tests were used for business workflows, role access, and user-interface behavior.

Tools used:
- PHP CLI using php -l for syntax verification.
- Browser execution on localhost for end-to-end functional tests.
- Apache and MySQL runtime stack for full application execution.

### 5.3.1 Description of Test Environment
| Item | Description |
|---|---|
| Operating System | Windows |
| Web Server | Apache on localhost |
| Runtime | PHP |
| Database | MySQL database named food_inventory |
| Browser Endpoint | http://localhost/IS1/Digital-food-inventory-management-system/ |
| Roles Under Test | admin, manager, chef, waiter |

### 5.3.2 Test Data Used
| Data Group | Test Data |
|---|---|
| Login Credentials | admin@gmail.com, manager@gmail.com, waiter@gmail.com, password attempts including 1234 and invalid variants |
| Order Inputs | Table values T21 and T99, menu item African Tea, non-existing item text NonExisting Item XYZ, quantity values 1 and 2 |
| Inventory Inputs | Ingredient records with stock, reorder level, unit, and cost |
| Analytics Inputs | Existing orders, order_items, wastage_logs, and chef_stock_notes records |

### 5.3.3 Core Functional Requirements Coverage
| FR ID | Core Functional Requirement | Related Test Case(s) |
|---|---|---|
| FR-01 | Login and role session handling | TC-01, TC-02 |
| FR-02 | Role-based authorization | TC-03 |
| FR-03 | Password change workflow | TC-04 |
| FR-04 | User management lifecycle | TC-05, TC-06, TC-07 |
| FR-05 | Ingredient master and stock visibility | TC-08 |
| FR-06 | Stock movement and wastage controls | TC-09 |
| FR-07 | Waiter order creation and validation | TC-10, TC-11 |
| FR-08 | Open menu access and filters | TC-12 |
| FR-09 | Chef inventory transaction workflow | TC-13 |
| FR-10 | Chef stock escalation notes | TC-14 |
| FR-11 | Manager threshold and menu governance | TC-15, TC-16 |
| FR-12 | Predictive report generation | TC-17 |
| FR-13 | Export reporting | TC-18 |
| FR-14 | Audit visibility | TC-19 |
| FR-15 | Chef-note acknowledgement loop | TC-20 |

### 5.3.4 Complete Individual Test Cases

Use the following standard execution format for each test case in Word when detailed step proof is required:

| Field | Value |
|---|---|
| Test Case ID | Example: TC-01 |
| Type of Test | Functional, Negative, Security, End-to-End |
| Functionality Tested | State the exact feature under test |
| Reason for Test | Why this test is necessary |
| Test Environment | OS, server, runtime, database |
| Test Tool | Browser or CLI tool |
| Test Data | Input values used in this test |
| Pre-conditions | Required state before running the test |
| Pass Criteria | What must be true for Pass |
| Appendix Reference | Appendix evidence ID |

Then add a step execution table directly below that case:

| Step No. | Test Procedure | Expected Outcome | Actual Outcome | Step Status |
|---|---|---|---|---|
| 1 | Open target page/module | Page opens correctly | Update after run | Pass or Fail |
| 2 | Enter test input data | Input accepted or validation shown | Update after run | Pass or Fail |
| 3 | Click submit/action button | Correct business response shown | Update after run | Pass or Fail |
| 4 | Verify database/list/grid result | Data state matches expected behavior | Update after run | Pass or Fail |

Final test decision for each case should be reported as:
- Overall Result: Pass or Fail.
- Evidence: screenshot file name and appendix ID.

### Test Case 1: Functional Validation, Waiter Login Success
| Field | Value |
|---|---|
| Test Case ID | TC-01 |
| Type of Test | Functional Validation (Manual) |
| Functionality Tested | Valid waiter login and dashboard redirect |
| Reason for Test | Confirms operational access for waiter workflow |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | waiter@gmail.com with valid password |
| Steps | Open login page, enter waiter credentials, click Login |
| Expected Result | Redirect to waiter dashboard |
| Actual Result | Redirect to waiter dashboard confirmed |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A1 |

### Test Case 2: Negative Functional, Invalid Password
| Field | Value |
|---|---|
| Test Case ID | TC-02 |
| Type of Test | Negative Functional Validation (Manual) |
| Functionality Tested | Login rejection on invalid password |
| Reason for Test | Prevents unauthorized access |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | admin@gmail.com with wrong password |
| Steps | Enter valid email and wrong password, submit |
| Expected Result | Login denied and error message shown |
| Actual Result | Invalid password message displayed |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A2 |

### Test Case 3: Security, Cross-Role Access Restriction
| Field | Value |
|---|---|
| Test Case ID | TC-03 |
| Type of Test | Security and Authorization |
| Functionality Tested | Manager attempt to open admin-only page |
| Reason for Test | Ensures role boundary enforcement |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser URL navigation |
| Test Data | Logged-in manager session and admin page URL |
| Steps | Login as manager, navigate directly to admin manage users page |
| Expected Result | Access denied and redirected away from admin resource |
| Actual Result | Redirected to login page, access denied |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A3 |

### Test Case 4: Functional, Change Password
| Field | Value |
|---|---|
| Test Case ID | TC-04 |
| Type of Test | Functional Validation |
| Functionality Tested | Password update with current password verification |
| Reason for Test | Account security and credential lifecycle |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Current password, new password, confirmation |
| Steps | Open change-password page, submit valid and invalid combinations |
| Expected Result | Valid update succeeds, invalid input is blocked |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A4 |

### Test Case 5: Functional, Admin Create User
| Field | Value |
|---|---|
| Test Case ID | TC-05 |
| Type of Test | Functional Verification |
| Functionality Tested | Create account in manage users module |
| Reason for Test | Mandatory admin provisioning function |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Name, email, phone, role |
| Steps | Open manage users, complete add-user form, submit |
| Expected Result | User created with success message |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A5 |

### Test Case 6: Functional, Admin Update and Password Reset
| Field | Value |
|---|---|
| Test Case ID | TC-06 |
| Type of Test | Functional Verification |
| Functionality Tested | Update user profile, active flag, and reset password |
| Reason for Test | Maintains user lifecycle and recovery controls |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Existing user row edit data and reset option |
| Steps | Edit user row values, optionally tick reset password, click Update |
| Expected Result | User row updates and optional reset succeeds |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A6 |

### Test Case 7: Negative Functional, Admin Delete Constraints
| Field | Value |
|---|---|
| Test Case ID | TC-07 |
| Type of Test | Negative Functional Verification |
| Functionality Tested | Protected deletion scenarios |
| Reason for Test | Prevents self-lockout and integrity violations |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Self account or referenced user account |
| Steps | Attempt delete action on protected account |
| Expected Result | Delete blocked with meaningful message |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A7 |

### Test Case 8: Functional, Ingredient Add and Status Logic
| Field | Value |
|---|---|
| Test Case ID | TC-08 |
| Type of Test | Functional Verification |
| Functionality Tested | Ingredient insertion and stock status rendering |
| Reason for Test | Core inventory data integrity |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Ingredient name, category, unit, current stock, reorder level, unit cost |
| Steps | Add ingredient and verify table row and status |
| Expected Result | Ingredient appears with correct stock status |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A8 |

### Test Case 9: Functional, Stock Movement and Wastage
| Field | Value |
|---|---|
| Test Case ID | TC-09 |
| Type of Test | Functional Verification |
| Functionality Tested | Stock movement effects and wastage logging |
| Reason for Test | Maintains accurate inventory movement audit |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Ingredient, movement type, quantity, notes |
| Steps | Submit stock movement and inspect updated stock plus history |
| Expected Result | Stock updates correctly and logs are recorded |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A9 |

### Test Case 10: End-to-End Validation, Waiter Order Success
| Field | Value |
|---|---|
| Test Case ID | TC-10 |
| Type of Test | End-to-End Functional Validation |
| Functionality Tested | Order creation for available menu item |
| Reason for Test | Critical service workflow |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Table T21, menu item African Tea, quantity 2 |
| Steps | Open waiter orders, provide details, click Create Order |
| Expected Result | Order number generated and new pending row shown |
| Actual Result | Order created successfully and listed as pending |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A10 |

### Test Case 11: Negative Validation, Unavailable Menu Item
| Field | Value |
|---|---|
| Test Case ID | TC-11 |
| Type of Test | Negative Functional Validation |
| Functionality Tested | Rejection of non-existing menu item |
| Reason for Test | Prevents invalid order creation |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Item text NonExisting Item XYZ |
| Steps | Attempt order with non-existing item and submit |
| Expected Result | Order blocked and user notified |
| Actual Result | Unavailable item alert shown and order not created |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A11 |

### Test Case 12: Functional Validation, Open Menu Filters
| Field | Value |
|---|---|
| Test Case ID | TC-12 |
| Type of Test | Functional UI Validation |
| Functionality Tested | Search and category filter in open menu |
| Reason for Test | Supports waiter and manager menu discovery |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Search term and category value |
| Steps | Open menu page, apply query and category filter |
| Expected Result | Matching results returned |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A12 |

### Test Case 13: Functional Verification, Chef Inventory Usage and Wastage
| Field | Value |
|---|---|
| Test Case ID | TC-13 |
| Type of Test | Functional Verification |
| Functionality Tested | Chef usage and wastage logging |
| Reason for Test | Kitchen transaction tracking |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Ingredient, action type, quantity, note |
| Steps | Submit chef inventory action and verify result |
| Expected Result | Stock reduced and movement record added |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A13 |

### Test Case 14: Functional Validation, Chef Stock Note Submission
| Field | Value |
|---|---|
| Test Case ID | TC-14 |
| Type of Test | Functional Validation |
| Functionality Tested | Chef low-stock and shelf-life note submission |
| Reason for Test | Manager visibility for replenishment and expiry risk |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Ingredient, observed stock, urgency, comment, optional shelf-life fields |
| Steps | Submit stock note and verify manager visibility |
| Expected Result | Note appears in manager reports queue |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A14 |

### Test Case 15: Functional Verification, Manager Threshold Update
| Field | Value |
|---|---|
| Test Case ID | TC-15 |
| Type of Test | Functional Verification |
| Functionality Tested | Reorder level update |
| Reason for Test | Controls alert and restock behavior |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Numeric reorder level value |
| Steps | Update threshold and save |
| Expected Result | Threshold persists in ingredient record |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A15 |

### Test Case 16: Functional Verification, Manager Menu Add or Update
| Field | Value |
|---|---|
| Test Case ID | TC-16 |
| Type of Test | Functional Verification |
| Functionality Tested | Add new menu item or update existing item |
| Reason for Test | Menu governance and pricing control |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Item name, category, price, availability |
| Steps | Submit menu-management form |
| Expected Result | Item added or existing item updated |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A16 |

### Test Case 17: Functional Validation, Predictive Report Generation
| Field | Value |
|---|---|
| Test Case ID | TC-17 |
| Type of Test | Functional Analytics Validation |
| Functionality Tested | Generate current-month predictive report |
| Reason for Test | Decision-support output for manager planning |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Current month sales and order history |
| Steps | Open manager controls, click Generate Report |
| Expected Result | Report generated with month label and analysis text |
| Actual Result | Message displayed: Predictive report generated for July 2026 |
| Pass/Fail | Pass |
| Appendix Reference | Appendix A17 |

### Test Case 18: Functional Verification, CSV Exports
| Field | Value |
|---|---|
| Test Case ID | TC-18 |
| Type of Test | Functional Verification |
| Functionality Tested | Export overview, monthly, low-stock, predictive, and chef-notes CSV |
| Reason for Test | External reporting and audit support |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Existing records in report sources |
| Steps | Open manager reports and trigger each export link |
| Expected Result | CSV files download with expected columns |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A18 |

### Test Case 19: Functional Validation, System Audit Visibility
| Field | Value |
|---|---|
| Test Case ID | TC-19 |
| Type of Test | Functional Validation |
| Functionality Tested | Admin audit tables for users and stock activity |
| Reason for Test | Governance and traceability |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Existing user and movement records |
| Steps | Open system audit page and verify records |
| Expected Result | Audit data renders correctly |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A19 |

### Test Case 20: Functional Verification, Chef Note Acknowledge and Reopen
| Field | Value |
|---|---|
| Test Case ID | TC-20 |
| Type of Test | Functional Verification |
| Functionality Tested | Manager acknowledgement state toggle for chef note |
| Reason for Test | Completes kitchen-to-manager workflow loop |
| Test Environment | Local Apache, PHP, MySQL on Windows |
| Test Tool | Browser |
| Test Data | Existing chef note and action acknowledge or reopen |
| Steps | Open manager reports, toggle note status, verify state change |
| Expected Result | Status and acknowledgment metadata update |
| Actual Result | Not yet executed in this cycle |
| Pass/Fail | Pending |
| Appendix Reference | Appendix A20 |

### 5.3.5 Automated Verification Summary
| Check | Scope | Result |
|---|---|---|
| PHP Syntax Lint | All PHP files in project | Pass, no syntax errors detected |

Evidence reference: Appendix A0.

### 5.3.6 Validation Summary
Executed and passed in this testing cycle:
- TC-01
- TC-02
- TC-03
- TC-10
- TC-11
- TC-17

Pending execution:
- TC-04 to TC-09
- TC-12 to TC-16
- TC-18 to TC-20

Current conclusion: the system has confirmed behavior on core login, authorization, waiter order flow, invalid-order blocking, and predictive report generation. Remaining pending tests should be completed and evidenced before final project sign-off.

### 5.3.7 How to Run Tests and Capture Screenshots
1. Execute one test case at a time in sequence from TC-01 to TC-20.
2. Use the exact test data listed in section 5.6.
3. Stop at the screen that proves the expected outcome.
4. Capture screenshot using Windows snipping tool.
5. Save image using appendix naming, for example A1_TC01_login_success.png.
6. Place files in docs/evidence.
7. Update Actual Result and Pass/Fail in this chapter after each run.

### 5.3.8 Chapter to Appendix Referencing Rule
Chapter 5 must remain text-only. All screenshots and raw command outputs must remain in appendices and be referenced by appendix ID.

### 5.3.9 Final Answers (Testing Outcomes)
This subsection provides the final direct answers required for Chapter 5 testing.

| Question | Final Answer |
|---|---|
| Was testing done for both Verification and Validation | Yes |
| Was the system tested as fit for purpose | Yes, on executed core workflows |
| Were tests manual or automated | Both manual and automated |
| Which tools were used | PHP CLI (php -l), Browser execution, Apache + MySQL runtime |
| Were complete individual test cases documented | Yes, TC-01 to TC-20 |
| Were expected and actual outcomes captured | Yes, with pass or pending status |
| Are screenshots in Chapter 5 body | No, screenshots are in appendices only |

Final execution status in this cycle:

| Metric | Value |
|---|---|
| Total planned test cases | 20 |
| Executed and passed | 6 (TC-01, TC-02, TC-03, TC-10, TC-11, TC-17) |
| Failed | 0 in executed set |
| Pending execution | 14 |

Executed evidence mapping:
- TC-01 -> Appendix A1
- TC-02 -> Appendix A2
- TC-03 -> Appendix A3
- TC-10 -> Appendix A10
- TC-11 -> Appendix A11
- TC-17 -> Appendix A17

Conclusion:
The implemented solution passed all executed critical tests and is operational for the validated core paths. Full sign-off requires completion of all pending test cases and corresponding appendix evidence.

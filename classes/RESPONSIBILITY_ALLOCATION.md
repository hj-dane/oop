# Class Responsibility Allocation for 4 Developers
## Based on InSync Use Cases (Admin, Manager, Employee roles)

---

## **Person 1: Admin - User & System Management**
**Role**: System Administrator
**Responsibilities**: User lifecycle, authentication, role management, system configuration

### Classes:
1. **User.php** - ADMIN USE CASES
   - **UC-01: Register New User** → Create employee accounts with roles
   - **UC-02: Manage User Accounts** → Edit user profiles, assign departments, change roles
   - **UC-03: User Authentication** → Login verification with password hashing
   - **UC-04: Email Verification** → Verify user email addresses
   - **UC-05: Manage User Status** → Activate/deactivate accounts
   - **UC-06: View User Details** → Access full user profiles for management
   
   **Key Methods**:
   - `hashPassword()`, `verifyPassword()`, `setPassword()` - Security
   - `verify()` - Email verification workflow
   - `getFullName()` - Display user info
   - `findByEmail()`, `findByUsername()`, `findById()` - User lookup
   - `save()`, `delete()` - Account management

---

## **Person 2: Manager - Project & Work Delegation**
**Role**: Project Manager
**Responsibilities**: Project planning, task assignment, team coordination, work tracking

### Classes:
1. **Project.php** - MANAGER USE CASES
   - **UC-07: Create Project** → Create new projects for department
   - **UC-08: Plan Project Timeline** → Set start/end dates, manage milestones
   - **UC-09: Assign Project to Department** → Link projects to departments
   - **UC-10: Manage Project Status** → Track project lifecycle (planning → active → completed → archived)
   - **UC-11: View Project Progress** → Monitor completion percentage
   - **UC-12: Delegate Tasks to Team** → Break project into tasks
   
   **Key Methods**:
   - `getProgress()` - Project completion percentage
   - `isOverdue()`, `isDueSoon()` - Deadline monitoring
   - `getTaskCount()`, `getCompletedTaskCount()` - Workload metrics
   - `archive()` - Move to archive when completed
   - `findById()`, `getByDepartment()`, `getByManager()` - Project queries
   - `save()`, `delete()` - Project CRUD

2. **Task.php** - MANAGER USE CASES
   - **UC-13: Create Task** → Create tasks within projects
   - **UC-14: Assign Task** → Assign tasks to team members
   - **UC-15: Update Task Status** → Track progress (Pending → In Progress → Completed)
   - **UC-16: Monitor Task Deadlines** → Alert on overdue/due-soon tasks
   - **UC-17: View Team Tasks** → See all tasks assigned by manager
   - **UC-18: Audit Task Changes** → Review task history for accountability
   
   **Key Methods**:
   - `updateStatus()` - Status change with history logging
   - `isOverdue()`, `isDueSoon()` - Deadline detection
   - `getHistory()` - Audit trail
   - `findById()`, `findAssignedTo()` (who I assigned to), `findAssignedBy()` (who assigned to me), `getByProject()` - Task queries
   - `save()`, `delete()` - Task CRUD

---

## **Person 3: Employee - Team Coordination & Communication**
**Role**: Employee / Team Member
**Responsibilities**: Department management, team communication, feedback submission

### Classes:
1. **Department.php** - EMPLOYEE USE CASES
   - **UC-19: View Department Info** → Access department details and structure
   - **UC-20: See Department Projects** → View projects assigned to department
   - **UC-21: View Team Members** → See colleagues in department
   - **UC-22: Department Communication** → Coordinate with team
   
   **Key Methods**:
   - `getProjects()`, `getEmployees()` - Relationship queries
   - `getProjectCount()` - Workload overview
   - `findById()`, `getAll()` - Department lookup
   - `save()`, `delete()` - Admin operations

2. **Announcement.php** - EMPLOYEE USE CASES
   - **UC-23: Receive System Announcements** → View company-wide announcements
   - **UC-24: Receive Department Announcements** → Get department-specific messages
   - **UC-25: View Announcements by Role** → See role-specific communications
   - **UC-26: Check Announcement Author** → Know who published announcement
   
   **Key Methods**:
   - `getActive()` - Current announcements
   - `getByAudience()` - Filter by audience (all/department/role)
   - `getAuthor()` - Announcement creator info
   - `findById()`, `getAll()` - Announcement lookup
   - `save()`, `delete()` - Admin operations

3. **Feedback.php** - EMPLOYEE USE CASES
   - **UC-27: Submit Feedback** → Send feedback to management
   - **UC-28: Track Feedback Status** → Monitor response to feedback (submitted → reviewed → resolved)
   - **UC-29: View Feedback Response** → Get admin/manager responses
   - **UC-30: Provide Additional Comments** → Add follow-up remarks to feedback
   
   **Key Methods**:
   - `getByStatus()` - Filter feedback by status
   - `getByUser()` - My feedback submissions
   - `updateStatus()` - Status transitions
   - `addResponse()` - Manager/admin responses
   - `findById()`, `getAll()` - Feedback lookup
   - `save()`, `delete()` - Feedback CRUD

---

## **Person 4: Admin/Manager - Reporting, Analytics & Audit**
**Role**: Data Analysis & Compliance Officer
**Responsibilities**: Report generation, performance analytics, audit logging, compliance tracking

### Classes:
1. **Report.php** - ANALYTICS USE CASES
   - **UC-31: Generate Project Reports** → Performance metrics per project
   - **UC-32: Generate Task Reports** → Task completion analytics
   - **UC-33: Generate Resource Reports** → Employee productivity metrics
   - **UC-34: Manager Performance Tracking** → Track manager effectiveness
   - **UC-35: Department Workload Analysis** → Department productivity metrics
   
   **Key Methods**:
   - `getByType()` - Filter reports (project/task/resource/performance)
   - `getByUser()` - User-specific reports
   - `getGenerator()` - Who created report
   - `findById()`, `getAll()` - Report queries
   - `save()`, `delete()` - Report CRUD

2. **TaskHistory.php** - AUDIT USE CASES
   - **UC-36: Track Task Changes** → Audit trail for task modifications
   - **UC-37: View Task Status Timeline** → Historical progression of task status
   - **UC-38: Identify Who Changed Task** → Track responsibility for changes
   - **UC-39: Compliance Audit** → Review task change records for compliance
   
   **Key Methods**:
   - `getTaskHistory()` - Changes for specific task
   - `getByUser()` - Changes made by user
   - `getUpdater()` - User who made change
   - `findById()`, `getAll()`, `getByTask()` - History queries
   - `save()` (insert-only - immutable)

3. **LogHistory.php** - SECURITY & COMPLIANCE USE CASES
   - **UC-40: Log User Actions** → Track all user activities (login/logout/file access/data changes)
   - **UC-41: Security Monitoring** → Detect suspicious activities
   - **UC-42: Compliance Reporting** → Audit trail for regulatory requirements
   - **UC-43: User Activity Analytics** → Understand user behavior patterns
   
   **Key Methods**:
   - `logAction()` (static) - Record user actions
   - `getByUser()` - User activity log
   - `getByAction()` - Filter by action type
   - `getActionName()` - Action description/label
   - `findById()`, `getAll()` - Log queries
   - `save()` (insert-only - immutable audit log)

4. **Archive.php** - DATA RETENTION USE CASES
   - **UC-44: Archive Completed Projects** → Move finished projects to archive
   - **UC-45: Archive Retired Departments** → Archive inactive departments
   - **UC-46: Archive Old Tasks** → Archive completed tasks
   - **UC-47: Maintain Data Retention** → Track what's archived and when
   - **UC-48: Restore From Archive** → Unarchive if needed
   
   **Key Methods**:
   - `archiveEntity()` (static) - Archive department/project/task
   - `getByType()` - Filter by entity type
   - `getByDepartment()`, `getByProject()` - Related archives
   - `findById()`, `getAll()` - Archive queries
   - `save()`, `delete()` - Archive CRUD

---

## **Workload Summary**

| Person | Role | Classes | Use Cases | Focus Area |
|--------|------|---------|-----------|-----------|
| **Person 1** | Admin | User | UC-01 to UC-06 (6 cases) | User & Authentication |
| **Person 2** | Manager | Project, Task | UC-07 to UC-18 (12 cases) | Project & Work Management |
| **Person 3** | Employee | Department, Announcement, Feedback | UC-19 to UC-30 (12 cases) | Team Coordination & Communication |
| **Person 4** | Admin/Manager | Report, TaskHistory, LogHistory, Archive | UC-31 to UC-48 (18 cases) | Reporting, Analytics & Audit |

**Total Use Cases**: 48 use cases distributed across team
**Methods per Person**: ~20-25 methods each
**Responsibility Distribution**: Balanced by role and complexity

## **Integration Points**

```
USE CASE FLOW:

UC-01-06: Admin creates users → Person 1 (User Management)
   ↓
UC-07-18: Managers organize work → Person 2 (Project & Task Management)
   ↓
UC-19-30: Employees execute work & get updates → Person 3 (Team Communication)
   ↓
UC-31-48: Analytics & audit logged → Person 4 (Reporting & Audit)

CROSS-TEAM DEPENDENCIES:
├─ User (Person 1) → used by all other classes for user tracking
├─ Project (Person 2) → depends on User & Department, generates Reports
├─ Task (Person 2) → depends on User & Project, creates TaskHistory
├─ Department (Person 3) → depends on User, linked to Projects
├─ Announcement (Person 3) → created by Manager/Admin, read by Employee
├─ Feedback (Person 3) → submitted by Employee, tracked by Admin
├─ Report (Person 4) → aggregates from Project, Task, User
├─ TaskHistory (Person 4) → generated by Task updates
├─ LogHistory (Person 4) → tracks all user actions
└─ Archive (Person 4) → archives from Project, Task, Department
```

---

## **Development Order Recommendation**

**Phase 1** (Foundation):
1. Person 1 → User.php (UC-01 to UC-06)
   - All other modules depend on User

**Phase 2** (Core Work Management):
2. Person 2 → Project.php (UC-07 to UC-12) - depends on User, Department
3. Person 2 → Task.php (UC-13 to UC-18) - depends on User, Project

**Phase 3** (Team Coordination):
4. Person 3 → Department.php (UC-19 to UC-22) - depends on User, Project
5. Person 3 → Announcement.php (UC-23 to UC-26) - depends on User
6. Person 3 → Feedback.php (UC-27 to UC-30) - depends on User

**Phase 4** (Analytics & Reporting):
7. Person 4 → LogHistory.php (UC-40 to UC-43) - depends on User
8. Person 4 → TaskHistory.php (UC-36 to UC-39) - depends on Task
9. Person 4 → Report.php (UC-31 to UC-35) - depends on all above
10. Person 4 → Archive.php (UC-44 to UC-48) - depends on Project, Task, Department

---

## **Git Branch Naming Convention**

```
feature/admin-user-management        → Person 1 (UC-01 to UC-06)
feature/manager-project-management   → Person 2 (UC-07 to UC-12)
feature/manager-task-management      → Person 2 (UC-13 to UC-18)
feature/employee-department-view     → Person 3 (UC-19 to UC-22)
feature/employee-announcements       → Person 3 (UC-23 to UC-26)
feature/employee-feedback            → Person 3 (UC-27 to UC-30)
feature/analytics-reports            → Person 4 (UC-31 to UC-35)
feature/audit-task-history           → Person 4 (UC-36 to UC-39)
feature/security-log-history         → Person 4 (UC-40 to UC-43)
feature/data-archival                → Person 4 (UC-44 to UC-48)
```

---

## **Code Review Checklist by Person**

### **Person 1 (Admin - User Management)**
Review UC-01 to UC-06:
- ✅ Password security (bcrypt/PASSWORD_DEFAULT hashing)
- ✅ Email validation and verification flow
- ✅ Role-based permission enforcement
- ✅ Session/token handling for authentication
- ✅ Account status (active/inactive) properly enforced
- ✅ Input validation and SQL injection prevention
- ✅ GDPR compliance (user data privacy)

### **Person 2 (Manager - Project & Task Management)**
Review UC-07 to UC-18:
- ✅ Project deadline calculations accurate
- ✅ Task progress percentage correct (completed/total)
- ✅ Status transitions valid (Pending → In Progress → Completed)
- ✅ Task history logged on every status change
- ✅ Task assignment authorization (manager can only assign to their projects)
- ✅ Overdue detection logic working
- ✅ Department/project relationships maintained

### **Person 3 (Employee - Team Coordination & Communication)**
Review UC-19 to UC-30:
- ✅ Department visibility (employee sees their own department)
- ✅ Announcement audience targeting correct (all/dept/role-specific)
- ✅ Feedback status flow valid (submitted → reviewed → resolved)
- ✅ Employee cannot modify/delete feedback (only view)
- ✅ Admin responses properly linked to feedback
- ✅ Announcement dates/expiry enforced
- ✅ Department relationships intact (projects, employees)

### **Person 4 (Admin/Manager - Reporting, Analytics & Audit)**
Review UC-31 to UC-48:
- ✅ Report data accuracy (calculations, filtering)
- ✅ Log immutability enforced (insert-only, no updates)
- ✅ Task history captures all relevant changes
- ✅ User action logging complete (login/logout/data changes)
- ✅ Archive timestamps consistent
- ✅ Soft-delete working (archive instead of hard delete)
- ✅ Report generation performance (optimized queries)
- ✅ Data retention policies enforced
- ✅ Audit trail provides full accountability

---

## **Complete Use Case Matrix**

| UC # | Use Case Name | Actor | Class | Person |
|------|---------------|-------|-------|--------|
| UC-01 | Register New User | Admin | User | 1 |
| UC-02 | Manage User Accounts | Admin | User | 1 |
| UC-03 | User Authentication | All | User | 1 |
| UC-04 | Email Verification | Admin | User | 1 |
| UC-05 | Manage User Status | Admin | User | 1 |
| UC-06 | View User Details | Admin | User | 1 |
| UC-07 | Create Project | Manager | Project | 2 |
| UC-08 | Plan Project Timeline | Manager | Project | 2 |
| UC-09 | Assign Project to Department | Manager | Project | 2 |
| UC-10 | Manage Project Status | Manager | Project | 2 |
| UC-11 | View Project Progress | Manager | Project | 2 |
| UC-12 | Delegate Tasks to Team | Manager | Project | 2 |
| UC-13 | Create Task | Manager | Task | 2 |
| UC-14 | Assign Task | Manager | Task | 2 |
| UC-15 | Update Task Status | Employee | Task | 2 |
| UC-16 | Monitor Task Deadlines | Manager | Task | 2 |
| UC-17 | View Team Tasks | Manager | Task | 2 |
| UC-18 | Audit Task Changes | Manager | Task | 2 |
| UC-19 | View Department Info | Employee | Department | 3 |
| UC-20 | See Department Projects | Employee | Department | 3 |
| UC-21 | View Team Members | Employee | Department | 3 |
| UC-22 | Department Communication | Employee | Department | 3 |
| UC-23 | Receive System Announcements | Employee | Announcement | 3 |
| UC-24 | Receive Department Announcements | Employee | Announcement | 3 |
| UC-25 | View Role-Specific Communications | Employee | Announcement | 3 |
| UC-26 | Check Announcement Author | Employee | Announcement | 3 |
| UC-27 | Submit Feedback | Employee | Feedback | 3 |
| UC-28 | Track Feedback Status | Employee | Feedback | 3 |
| UC-29 | View Feedback Response | Employee | Feedback | 3 |
| UC-30 | Provide Additional Comments | Employee | Feedback | 3 |
| UC-31 | Generate Project Reports | Manager/Admin | Report | 4 |
| UC-32 | Generate Task Reports | Manager/Admin | Report | 4 |
| UC-33 | Generate Resource Reports | Admin | Report | 4 |
| UC-34 | Manager Performance Tracking | Admin | Report | 4 |
| UC-35 | Department Workload Analysis | Manager/Admin | Report | 4 |
| UC-36 | Track Task Changes | Manager/Admin | TaskHistory | 4 |
| UC-37 | View Task Status Timeline | Manager/Admin | TaskHistory | 4 |
| UC-38 | Identify Who Changed Task | Manager/Admin | TaskHistory | 4 |
| UC-39 | Compliance Audit | Admin | TaskHistory | 4 |
| UC-40 | Log User Actions | System | LogHistory | 4 |
| UC-41 | Security Monitoring | Admin | LogHistory | 4 |
| UC-42 | Compliance Reporting | Admin | LogHistory | 4 |
| UC-43 | User Activity Analytics | Admin | LogHistory | 4 |
| UC-44 | Archive Completed Projects | Admin | Archive | 4 |
| UC-45 | Archive Retired Departments | Admin | Archive | 4 |
| UC-46 | Archive Old Tasks | Admin | Archive | 4 |
| UC-47 | Maintain Data Retention | Admin | Archive | 4 |
| UC-48 | Restore From Archive | Admin | Archive | 4 |

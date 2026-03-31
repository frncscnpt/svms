# Push Notification Triggers - Complete List

All actions that trigger push notifications in SVMS.

---

## 📋 Summary

Total Triggers: **7 actions**
- 4 triggers for Students
- 2 triggers for Admins/Discipline Officers  
- 1 trigger for Teachers

---

## 🎯 Detailed List

### 1️⃣ Teacher Files Violation Report

**File:** `teacher/report.php` (line 44-67)

**Trigger:** When a teacher submits a violation report

**Recipients:**
- ✅ **Student** (the one being reported)
  - Title: "New Violation Recorded"
  - Message: "A violation has been filed against you for {violation_type}. Please check your dashboard."
  - Link: `/student/violations.php`
  - Type: `danger`

- ✅ **All Admins & Discipline Officers**
  - Title: "New Violation Reported"
  - Message: "A new violation has been reported by {teacher_name}. Please review."
  - Link: `/discipline/violations.php`
  - Type: `warning`

**Code:**
```php
// Notify student
addNotification(
    $studentUserId,
    "New Violation Recorded",
    "A violation has been filed against you for {$typeName}. Please check your dashboard.",
    'danger',
    '/student/violations.php'
);

// Notify admins/discipline officers
foreach ($ops as $opId) {
    addNotification(
        $opId,
        "New Violation Reported",
        "A new violation has been reported by {$_SESSION['full_name']}. Please review.",
        'warning',
        '/discipline/violations.php'
    );
}
```

---

### 2️⃣ Admin Files Violation Report

**File:** `admin/report_violation.php` (line 49-67)

**Trigger:** When an admin directly files a violation report

**Recipients:**
- ✅ **Student** (the one being reported)
  - Title: "New Violation Recorded"
  - Message: "A violation has been filed against you for {violation_type}. Please check your dashboard."
  - Link: `/student/violations.php`
  - Type: `danger`

- ✅ **All Admins & Discipline Officers**
  - Title: "New Violation Reported"
  - Message: "A new violation has been reported by {admin_name}. Please review."
  - Link: `/discipline/violations.php`
  - Type: `warning`

**Code:** Same as Teacher Files Violation

---

### 3️⃣ Disciplinary Action Issued

**File:** `discipline/actions.php` (line 33-53)

**Trigger:** When a discipline officer issues a disciplinary action (warning, detention, suspension, etc.)

**Recipients:**
- ✅ **Teacher** (who filed the original report)
  - Title: "Report Reviewed"
  - Message: "Your violation report has been reviewed and action has been taken."
  - Link: `/teacher/my_reports.php`
  - Type: `info`

- ✅ **Student** (who received the action)
  - Title: "Disciplinary Action Issued"
  - Message: "A disciplinary action has been issued for your violation. Please check your dashboard."
  - Link: `/student/index.php`
  - Type: `danger`

**Code:**
```php
// Notify teacher who filed the report
addNotification(
    $repId,
    "Report Reviewed",
    "Your violation report has been reviewed and action has been taken.",
    'info',
    '/teacher/my_reports.php'
);

// Notify student
addNotification(
    $stuData['user_id'],
    "Disciplinary Action Issued",
    "A disciplinary action has been issued for your violation. Please check your dashboard.",
    'danger',
    '/student/index.php'
);
```

---

### 4️⃣ Uniform Pass Issued (Discipline Officer)

**File:** `discipline/uniform_passes.php` (line 33-36)

**Trigger:** When a discipline officer issues a temporary uniform pass

**Recipients:**
- ✅ **Student** (who received the pass)
  - Title: "Temporary Uniform Pass Issued"
  - Message: "A temporary uniform pass has been issued for you. Valid for today."
  - Link: `/student/uniform_pass.php`
  - Type: `success`

**Code:**
```php
addNotification(
    $stuUserId,
    'Temporary Uniform Pass Issued',
    'A temporary uniform pass has been issued for you. Valid for today.',
    'success',
    '/student/uniform_pass.php'
);
```

---

### 5️⃣ Uniform Pass Revoked (Discipline Officer)

**File:** `discipline/uniform_passes.php` (line 67-70)

**Trigger:** When a discipline officer revokes a uniform pass

**Recipients:**
- ✅ **Student** (whose pass was revoked)
  - Title: "Uniform Pass Revoked"
  - Message: "Your temporary uniform pass has been revoked."
  - Link: `/student/uniform_pass.php`
  - Type: `warning`

**Code:**
```php
addNotification(
    $stuUserId,
    'Uniform Pass Revoked',
    'Your temporary uniform pass has been revoked.',
    'warning',
    '/student/uniform_pass.php'
);
```

---

### 6️⃣ Uniform Pass Issued (Admin)

**File:** `admin/uniform_passes.php` (line 33-36)

**Trigger:** When an admin issues a temporary uniform pass

**Recipients:**
- ✅ **Student** (who received the pass)
  - Title: "Temporary Uniform Pass Issued"
  - Message: "A temporary uniform pass has been issued for you. Valid for today."
  - Link: `/student/uniform_pass.php`
  - Type: `success`

**Code:** Same as Discipline Officer Uniform Pass Issued

---

### 7️⃣ Uniform Pass Revoked (Admin)

**File:** `admin/uniform_passes.php` (line 64)

**Trigger:** When an admin revokes a uniform pass

**Recipients:**
- ✅ **Student** (whose pass was revoked)
  - Title: "Uniform Pass Revoked"
  - Message: "Your temporary uniform pass has been revoked."
  - Link: `/student/uniform_pass.php`
  - Type: `warning`

**Code:** Same as Discipline Officer Uniform Pass Revoked

---

## 📊 Notification Matrix

| Action | Student | Teacher | Admin | Discipline |
|--------|---------|---------|-------|------------|
| Teacher files violation | ✅ | ❌ | ✅ | ✅ |
| Admin files violation | ✅ | ❌ | ✅ | ✅ |
| Disciplinary action issued | ✅ | ✅ | ❌ | ❌ |
| Uniform pass issued (Discipline) | ✅ | ❌ | ❌ | ❌ |
| Uniform pass revoked (Discipline) | ✅ | ❌ | ❌ | ❌ |
| Uniform pass issued (Admin) | ✅ | ❌ | ❌ | ❌ |
| Uniform pass revoked (Admin) | ✅ | ❌ | ❌ | ❌ |

---

## 🎯 By Recipient

### Students Receive Notifications For:
1. ✅ New violation filed against them (by teacher or admin)
2. ✅ Disciplinary action issued
3. ✅ Uniform pass issued
4. ✅ Uniform pass revoked

### Teachers Receive Notifications For:
1. ✅ Their violation report was reviewed and action taken

### Admins Receive Notifications For:
1. ✅ New violation reported (by teacher or admin)

### Discipline Officers Receive Notifications For:
1. ✅ New violation reported (by teacher or admin)

---

## 🔔 Notification Types

| Type | Color | Icon | Usage |
|------|-------|------|-------|
| `danger` | Red | ⚠️ | Violations, disciplinary actions |
| `warning` | Yellow | ⚠️ | Pass revoked, pending reviews |
| `success` | Green | ✓ | Pass issued, positive actions |
| `info` | Blue | ℹ️ | Report reviewed, general updates |

---

## 🧪 Testing Each Trigger

### Test 1: Teacher Files Violation
```
1. Login as teacher
2. Go to "File Report"
3. Select a student
4. Fill violation details
5. Submit
✓ Student receives notification
✓ Admin/Discipline receives notification
```

### Test 2: Admin Files Violation
```
1. Login as admin
2. Go to "Report Violation"
3. Select a student
4. Fill violation details
5. Submit
✓ Student receives notification
✓ Other admins/discipline receive notification
```

### Test 3: Disciplinary Action Issued
```
1. Login as discipline officer
2. Go to "Violations"
3. Click "Review" on a pending violation
4. Issue an action (warning, detention, etc.)
5. Submit
✓ Student receives notification
✓ Teacher (who filed) receives notification
```

### Test 4: Uniform Pass Issued
```
1. Login as discipline officer or admin
2. Go to "Uniform Passes"
3. Click "Issue Pass"
4. Select student and reason
5. Submit
✓ Student receives notification
```

### Test 5: Uniform Pass Revoked
```
1. Login as discipline officer or admin
2. Go to "Uniform Passes"
3. Find active pass
4. Click "Revoke"
5. Confirm
✓ Student receives notification
```

---

## 📝 Notes

1. **All notifications are stored in database** (`notifications` table)
2. **Push notifications are sent only if user has subscribed**
3. **In-app notifications always work** (bell icon dropdown)
4. **Browser push requires subscription** (user must click "Enable Alerts")
5. **Failed push deliveries are logged** (check error.log)
6. **Expired subscriptions are auto-removed** (410/404 responses)

---

## 🔍 How to Check if Notifications Work

### Check In-App Notifications:
```
1. Click bell icon (🔔) in navigation
2. Should see notification in dropdown
3. Badge shows unread count
```

### Check Browser Push:
```
1. User must be subscribed (check push_subscriptions table)
2. Browser notification appears in system tray
3. Click notification opens SVMS
```

### Check Database:
```sql
-- See all notifications
SELECT * FROM notifications ORDER BY created_at DESC;

-- See push subscriptions
SELECT user_id, COUNT(*) as subscription_count 
FROM push_subscriptions 
GROUP BY user_id;

-- See unread notifications per user
SELECT user_id, COUNT(*) as unread_count 
FROM notifications 
WHERE is_read = 0 
GROUP BY user_id;
```

### Check Error Logs:
```
Location: C:\xampp\apache\logs\error.log

Look for:
- "Sending push to X subscription(s)..."
- "Push notification sent successfully..."
- "Push notification failed..."
```

---

## 🚀 Summary

**Total Notification Triggers: 7**
- 2 for violation reports (teacher + admin)
- 1 for disciplinary actions
- 4 for uniform passes (issue/revoke × 2 roles)

**Total Recipients: 4 roles**
- Students (most notifications)
- Teachers (report reviewed)
- Admins (new reports)
- Discipline Officers (new reports)

**Notification Delivery:**
- ✅ In-app (always works)
- ✅ Browser push (requires subscription)
- ✅ Database stored (permanent record)

---

**All push notifications are working with the web-push library!** 🎉

# Resume Builder System - Setup Instructions

## 📋 Overview
The Resume Builder System has been successfully implemented with all required features. Follow these steps to set up and test the system.

## 🔧 Setup Steps

### Step 1: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Ensure both services show green "Running" status

### Step 2: Initialize Database Schema
The database schema file has been created at: `c:/xampp/htdocs/placement/resume_schema.sql`

**Option A: Using phpMyAdmin (Recommended)**
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click on `placement_db` database in the left sidebar
3. Click on the **SQL** tab
4. Open the file `resume_schema.sql` in a text editor
5. Copy all the SQL content
6. Paste it into the SQL query box in phpMyAdmin
7. Click **Go** to execute

**Option B: Using MySQL Command Line**
```bash
cd c:\xampp\htdocs\placement
mysql -u root -p placement_db < resume_schema.sql
```
(Press Enter when prompted for password if no password is set)

### Step 3: Verify Database Tables
In phpMyAdmin, check that the following tables have been created:
- ✅ `resume_personal`
- ✅ `resume_education`
- ✅ `resume_skills`
- ✅ `resume_projects`
- ✅ `resume_experience`
- ✅ `resume_certifications`
- ✅ `resume_achievements`
- ✅ `resume_languages`
- ✅ `placement_eligibility`

## 🧪 Testing the System

### For Students:
1. **Login**: Go to `http://localhost/placement/auth/login.php`
2. **Access Resume Builder**: Click "Resume Builder" in the sidebar
3. **Fill Out Sections**:
   - Personal Information (required)
   - Education (add at least one)
   - Skills (add 5+ for full score)
   - Projects (add 2+ for full score)
   - Experience (add 1+ for full score)
   - Certifications (add 1+ for full score)
   - Achievements (optional)
   - Placement Eligibility (required)
4. **Check Resume Score**: Watch the score increase as you complete sections
5. **Preview Resume**: Click "Preview Resume" button
6. **Download PDF**: Click "Download PDF" in the preview page

### For Admin/Recruiters:
1. **Login as Admin**: Use admin credentials
2. **Access Student Resumes**: Click "Student Resumes" in the sidebar
3. **Test Filters**:
   - Filter by skill (e.g., "Python")
   - Filter by minimum resume score
   - Filter by relocation preference
   - Filter by CTC range
4. **View Student Resume**: Click "View Resume" on any student card
5. **Download PDF**: Click "Download PDF" in the student resume view

## 📊 Resume Score Calculation

The system automatically calculates a resume completeness score:
- **Personal Info**: 15%
- **Education**: 20%
- **Skills** (5+ skills): 15%
- **Projects** (2+ projects): 20%
- **Experience** (1+ experience): 15%
- **Certifications** (1+ cert): 10%
- **Eligibility**: 5%
- **Total**: 100%

## 🎯 Key Features Implemented

### Student Features:
- ✅ Comprehensive resume builder with 8 sections
- ✅ Real-time resume score indicator
- ✅ Tabbed interface for easy navigation
- ✅ Dynamic form fields (add/remove entries)
- ✅ Professional resume preview
- ✅ PDF generation via browser print
- ✅ Data persistence and auto-save

### Admin Features:
- ✅ ATS-style student filtering
- ✅ Filter by skills, score, CTC, relocation
- ✅ View student resume scores
- ✅ View complete student profiles
- ✅ Download student resumes as PDF
- ✅ Candidate comparison dashboard

## 🐛 Troubleshooting

### Database Connection Issues:
- Ensure MySQL is running in XAMPP
- Check database name is `placement_db`
- Verify credentials in `config/db.php`

### Tables Not Created:
- Re-run the SQL schema in phpMyAdmin
- Check for SQL errors in the phpMyAdmin interface

### Resume Not Saving:
- Check browser console for JavaScript errors
- Verify form fields are filled correctly
- Check database permissions

## 📁 Files Created

**Student Interface:**
- `student/resume_builder.php` - Main resume builder interface
- `student/resume_actions.php` - Backend handler for all resume operations
- `student/resume_view.php` - Resume preview and PDF generation

**Admin Interface:**
- `admin/student_resumes.php` - Student listing with filters
- `admin/view_student_resume.php` - Individual student resume view

**Database:**
- `resume_schema.sql` - Database schema for all resume tables

## ✅ Next Steps

1. Start XAMPP services
2. Run the database schema
3. Test the student resume builder
4. Test the admin filtering system
5. Verify PDF generation works correctly

The system is now ready for use! 🎉

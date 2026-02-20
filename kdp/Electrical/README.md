# Computer Engineering Department - Complete Setup

## ✅ Files Created

All files have been created in the `/Computer/` folder with a single configuration variable at the top of each file that can be changed for other departments.

### Configuration Variable (Change this for other departments):
```php
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
$DEPARTMENT_NAME = "Computer Engineering";
// ============================================
```

### Pages Created:

1. **index.php** - About Department
   - Displays department description and photo gallery
   - Modal viewer for photos
   - Path: `/Computer/index.php`

2. **notice-board.php** - Department Notice Board
   - DataTable with search, sort, pagination
   - Shows date, title, and view details button
   - Path: `/Computer/notice-board.php`

3. **notice-details.php** - Individual Notice Details
   - Displays full notice with file download
   - Path: `/Computer/notice-details.php?id={notice_id}`

4. **faculty.php** - Faculty Listing
   - Grid layout showing faculty cards
   - Photo, Name, Designation
   - Link to faculty profile
   - Path: `/Computer/faculty.php`

5. **faculty-profile.php** - Individual Faculty Profile
   - Header with photo and contact info
   - Tabs for: Education, Work Experience, Skills, Courses, Training, Research, Publications, Projects, Patents, Memberships, Awards, Expert Lectures, Portfolio, Extra Fields
   - Path: `/Computer/faculty-profile.php?id={faculty_id}`

6. **activities.php** - Department Activities
   - Lists all activities with photos
   - Category, date, remark display
   - Photo modal viewer
   - Path: `/Computer/activities.php`

7. **facilities.php** - Department Facilities
   - Lists all facilities with descriptions
   - Photo gallery for each facility
   - Modal viewer for photos
   - Path: `/Computer/facilities.php`

8. **newsletter.php** - Department Newsletters
   - Grid of newsletter cards
   - View and download PDF files
   - Path: `/Computer/newsletter.php`

9. **syllabus.php** - Course Syllabus
   - Links to external syllabus pages
   - Path: `/Computer/syllabus.php`

## 📁 File Structure

```
Computer/
├── index.php
├── notice-board.php
├── notice-details.php
├── faculty.php
├── faculty-profile.php
├── activities.php
├── facilities.php
├── newsletter.php
└── syllabus.php

assets/css/
└── dept-pages.css (NEW - Department pages styling)
```

## 🎨 CSS File

**dept-pages.css** has been created with all styling for:
- Department navigation
- Faculty cards and profile page
- Newsletter cards
- Syllabus cards
- Photo galleries
- Modals
- Tabs
- Responsive design

**Add this line to your head.php:**
```html
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/dept-pages.css">
```

## 🔄 Creating Other Departments

To create pages for another department (e.g., Mechanical Engineering):

### Step 1: Copy the Computer folder
```bash
cp -r Computer Mechanical
```

### Step 2: Change the variable in ALL files
In each file (index.php, faculty.php, etc.), change this line:
```php
$DEPARTMENT_NAME = "Computer Engineering";
```
to:
```php
$DEPARTMENT_NAME = "Mechanical Engineering";
```

### Step 3: Done!
All SQL queries will automatically filter by the new department name.

## 📊 Database Tables Used

1. **about_department** - Department info and photos
2. **nb** - Notice board (filtered by department)
3. **faculty** - Faculty members (filtered by department)
4. **activities** - Department activities (filtered by department)
5. **dept_facilities** - Department facilities (filtered by department)
6. **dept_newsletter** - Newsletters (filtered by department)
7. **dept_syllabus** - Syllabus links (filtered by department)

## 🔑 Key Features

### Department Navigation Bar
- Horizontal navigation on all pages
- Active state highlighting
- Responsive (scrollable on mobile)

### Faculty Profile Tabs
- Dynamic tabs based on available data
- Only shows tabs with content
- Supports extra fields (JSON)
- Responsive design

### Photo Galleries
- Click to open modal
- Navigate with arrows or keyboard
- Photo counter (e.g., "3/10")
- Close with Escape key
- Zoom hover effects

### DataTables Integration
- Search functionality
- Sort by columns
- Pagination (10, 25, 50, All)
- Responsive

## 🎯 Important Notes

1. **Single Variable Design**: Only change `$DEPARTMENT_NAME` at the top of each file
2. **Database Consistency**: Ensure department names in database match exactly
3. **Photo Paths**: All photos use `../Admin/` prefix
4. **SQL Injection**: Consider using prepared statements for production
5. **CSS Inclusion**: Make sure dept-pages.css is included in head.php

## 🚀 Testing Checklist

- [ ] All pages load without errors
- [ ] Department navigation works
- [ ] Notice board DataTable functions properly
- [ ] Faculty cards display correctly
- [ ] Faculty profile tabs work
- [ ] Photo modals open and navigate
- [ ] Newsletter PDFs download
- [ ] Syllabus links open
- [ ] Responsive design on mobile
- [ ] Back buttons work

## 📝 Example URLs

```
/Computer/index.php
/Computer/notice-board.php
/Computer/notice-details.php?id=1
/Computer/faculty.php
/Computer/faculty-profile.php?id=1
/Computer/activities.php
/Computer/facilities.php
/Computer/newsletter.php
/Computer/syllabus.php
```

## 🔧 Customization

To customize for your needs:
1. Edit dept-pages.css for styling changes
2. Modify the department navigation links in each file
3. Add new tabs to faculty-profile.php as needed
4. Adjust grid layouts in CSS

---

**Created**: December 16, 2025
**Author**: YRP
**Purpose**: Complete department page system for K.D. Polytechnic website

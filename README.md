# 📊 Manual Test Analysis Platform

A comprehensive PHP-based platform for manually logging and analyzing test performance with detailed statistics and insights.

## ✨ Features

### 🎯 Core Functionality
- **Manual Question Logging**: Log feelings, confidence, and performance for each question
- **Comprehensive Analytics**: Detailed statistics with interactive charts and graphs
- **Session Management**: Create and track multiple test sessions
- **Performance Insights**: AI-generated insights and recommendations

### 📊 Analytics & Insights
- **Feeling Distribution**: Track how you feel about questions (confident, guessed, confused, etc.)
- **Subject Performance**: Analyze accuracy across different subjects
- **Confidence vs Accuracy**: Correlation between confidence level and correct answers
- **Difficulty Analysis**: Performance across easy, medium, hard, and very hard questions
- **Time Analysis**: Track time spent on questions
- **Progress Tracking**: Monitor improvement over time

### 🎨 User Experience
- **Modern UI**: Clean, responsive design with intuitive navigation
- **Interactive Charts**: Powered by Chart.js for beautiful visualizations
- **Auto-save**: Form data automatically saved to prevent data loss
- **Progress Indicators**: Visual progress bars and completion tracking
- **Mobile Friendly**: Fully responsive design for all devices

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher with PDO MySQL extension
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Upload Files**
   ```bash
   # Upload all files to your web server directory
   # For Hostinger: upload to public_html folder
   ```

2. **Database Setup**
   ```sql
   # Import the database schema
   # Execute the SQL commands in database_setup.sql
   ```

3. **Configure Database**
   ```php
   // Edit config/database.php with your credentials
   $db_host = "localhost";
   $db_username = "your_username";
   $db_password = "your_password";
   $db_name = "your_database";
   ```

4. **Set Permissions**
   ```bash
   # Ensure proper file permissions
   chmod 755 assets/
   chmod 644 *.php
   ```

## 📁 Project Structure

```
Manual_Test_Analysis_Platform/
├── index.php                 # Dashboard homepage
├── create-session.php        # Create new test sessions
├── log-question.php          # Question logging interface
├── view-session.php          # Session details and analysis
├── analytics.php             # Comprehensive analytics dashboard
├── database_setup.sql        # Database schema and sample data
├── config/
│   └── database.php          # Database configuration and functions
├── assets/
│   ├── css/
│   │   └── style.css         # Complete styling
│   └── js/
│       └── main.js           # JavaScript functionality
└── README.md                 # This file
```

## 🎯 Usage Guide

### Creating a Test Session
1. Click "Create New Test Session" from the dashboard
2. Fill in test details (name, date, type, total questions)
3. Add optional notes
4. Click "Create Session & Start Logging"

### Logging Questions
1. Select your feeling about the question:
   - **Confident**: You were sure about the answer
   - **Guessed**: Made an educated guess
   - **Confused**: Unsure between options
   - **Blank**: Had no idea
   - **Time Pressure**: Rushed due to time
   - **Careless**: Knew answer but made mistake

2. Set confidence level (1-10 scale)
3. Choose difficulty level
4. Record time spent (optional)
5. Enter your answer and correct answer
6. Add subject, topic, and notes (optional)
7. Mark for review if needed

### Viewing Analytics
- **Dashboard**: Quick overview and recent sessions
- **Analytics**: Comprehensive charts and detailed breakdowns
- **Session Details**: Question-by-question analysis with insights

## 📊 Feeling Categories

The platform uses six fixed feeling categories for consistent analysis:

| Feeling | Description | Use Case |
|---------|-------------|----------|
| 😊 Confident | You were sure about the answer | Questions you answered with certainty |
| 🤔 Guessed | Made an educated guess | Narrowed down options but unsure |
| 😕 Confused | Unsure between options | Multiple options seemed correct |
| 😐 Blank | Had no idea about the answer | Complete lack of knowledge |
| ⏰ Time Pressure | Rushed due to time constraints | Time management issues |
| 🤦 Careless | Knew answer but made mistake | Silly mistakes or misreading |

## 🔧 Technical Details

### Database Schema
- **test_sessions**: Stores test session information
- **question_logs**: Main table for question analysis data
- **analysis_insights**: Generated insights and patterns

### Key Technologies
- **Backend**: PHP 7.4+ with PDO for database operations
- **Database**: MySQL with optimized indexes
- **Frontend**: Vanilla JavaScript with Chart.js
- **Styling**: Modern CSS with CSS Grid and Flexbox
- **Charts**: Chart.js for interactive visualizations

### Security Features
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- Session management for flash messages
- Input validation and sanitization

## 🎨 Customization

### Adding New Subjects
Edit the subject dropdown in `log-question.php`:
```php
<option value="New Subject">New Subject</option>
```

### Modifying Feeling Categories
Update the ENUM in `database_setup.sql`:
```sql
feeling ENUM('confident', 'guessed', 'confused', 'blank', 'time_pressure', 'careless', 'new_feeling')
```

### Styling Changes
Modify CSS variables in `assets/css/style.css`:
```css
:root {
    --primary-color: #2196F3;  /* Change primary color */
    --success-color: #4CAF50;  /* Change success color */
}
```

## 📈 Analytics Features

### Charts Available
- **Feelings Distribution**: Pie/Doughnut chart showing feeling breakdown
- **Subject Performance**: Bar chart of accuracy by subject
- **Confidence vs Accuracy**: Scatter plot correlation
- **Difficulty Analysis**: Multi-axis chart showing performance by difficulty

### Insights Generated
- Most common feeling patterns
- Best and worst performing subjects
- Confidence calibration accuracy
- Areas needing review
- Performance trends

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **Charts Not Loading**
   - Check internet connection (Chart.js CDN)
   - Verify JavaScript console for errors
   - Ensure proper data format

3. **Auto-save Not Working**
   - Check browser localStorage support
   - Clear browser cache and cookies
   - Verify JavaScript is enabled

### Performance Optimization
- Enable MySQL query caching
- Use production Chart.js build
- Optimize images and assets
- Enable gzip compression

## 🤝 Contributing

This platform was designed for UPSC test analysis but can be adapted for any examination system.

### Potential Enhancements
- Export data to CSV/Excel
- Comparison between multiple sessions
- Study plan generation based on weak areas
- Mobile app version
- Integration with online test platforms

## 📄 License

This project is created for educational and personal use. Feel free to modify and adapt for your needs.

## 🙏 Acknowledgments

- Chart.js for beautiful chart visualizations
- Modern CSS techniques for responsive design
- PHP community for best practices
- UPSC aspirants for inspiration

---

**Built for serious test takers who want deep insights into their performance patterns! 📊📈**
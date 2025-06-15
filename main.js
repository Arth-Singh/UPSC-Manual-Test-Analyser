// Manual Test Analysis Platform - Main JavaScript

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize the application
function initializeApp() {
    // Initialize all components
    initializeFlashMessages();
    initializeFormEnhancements();
    initializeAnimations();
    initializeTooltips();
    initializeLocalStorage();
    
    console.log('Manual Test Analysis Platform initialized successfully');
}

// Flash Messages Management
function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (message && message.parentElement) {
                fadeOut(message, 500);
            }
        }, 5000);
        
        // Close button functionality
        const closeBtn = message.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                fadeOut(message, 300);
            });
        }
    });
}

// Form Enhancements
function initializeFormEnhancements() {
    // Enhanced form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showFormErrors(this);
            }
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    // Confidence level slider enhancement
    const confidenceSlider = document.getElementById('confidence_level');
    if (confidenceSlider) {
        enhanceConfidenceSlider(confidenceSlider);
    }
    
    // Subject-topic relationship
    initializeSubjectTopicRelationship();
}

// Enhanced confidence slider
function enhanceConfidenceSlider(slider) {
    const display = document.getElementById('confidence_display');
    
    if (!display) return;
    
    slider.addEventListener('input', function() {
        const value = parseInt(this.value);
        display.textContent = value;
        
        // Change color based on confidence level
        display.style.color = getConfidenceColor(value);
        display.style.fontWeight = 'bold';
        display.style.fontSize = '1.2rem';
        
        // Add emoji indicator
        display.textContent = `${value} ${getConfidenceEmoji(value)}`;
    });
    
    // Initialize with current value
    slider.dispatchEvent(new Event('input'));
}

// Get confidence color
function getConfidenceColor(value) {
    if (value <= 3) return '#F44336'; // Red
    if (value <= 5) return '#FF9800'; // Orange
    if (value <= 7) return '#FFC107'; // Yellow
    if (value <= 8) return '#8BC34A'; // Light Green
    return '#4CAF50'; // Green
}

// Get confidence emoji
function getConfidenceEmoji(value) {
    if (value <= 2) return '😰';
    if (value <= 4) return '😐';
    if (value <= 6) return '🤔';
    if (value <= 8) return '😊';
    return '😎';
}

// Subject-Topic relationship enhancement
function initializeSubjectTopicRelationship() {
    const subjectSelect = document.getElementById('subject');
    const topicInput = document.getElementById('topic');
    
    if (!subjectSelect || !topicInput) return;
    
    const topicSuggestions = {
        'History': ['Ancient India', 'Medieval India', 'Modern India', 'World History', 'Art & Architecture'],
        'Geography': ['Physical Geography', 'Human Geography', 'Economic Geography', 'Environmental Geography', 'Indian Geography'],
        'Polity': ['Constitution', 'Fundamental Rights', 'Directive Principles', 'Parliament', 'Judiciary', 'Elections'],
        'Economy': ['Microeconomics', 'Macroeconomics', 'Indian Economy', 'Economic Reforms', 'Banking', 'Budget'],
        'Science': ['Physics', 'Chemistry', 'Biology', 'Space Technology', 'Nuclear Technology', 'Biotechnology'],
        'Environment': ['Ecology', 'Climate Change', 'Pollution', 'Conservation', 'Sustainable Development'],
        'Current Affairs': ['National', 'International', 'Sports', 'Awards', 'Schemes & Programs'],
        'Art & Culture': ['Dance', 'Music', 'Painting', 'Literature', 'Festivals', 'Monuments'],
        'Ethics': ['Theoretical Ethics', 'Applied Ethics', 'Case Studies', 'Aptitude', 'Emotional Intelligence']
    };
    
    subjectSelect.addEventListener('change', function() {
        const selectedSubject = this.value;
        const suggestions = topicSuggestions[selectedSubject] || [];
        
        // Create or update datalist
        let datalist = document.getElementById('topic-suggestions');
        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = 'topic-suggestions';
            document.body.appendChild(datalist);
            topicInput.setAttribute('list', 'topic-suggestions');
        }
        
        // Clear and populate
        datalist.innerHTML = '';
        suggestions.forEach(topic => {
            const option = document.createElement('option');
            option.value = topic;
            datalist.appendChild(option);
        });
    });
}

// Form validation
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Specific field validations
    switch (fieldName) {
        case 'total_questions':
            if (value && (parseInt(value) < 1 || parseInt(value) > 200)) {
                isValid = false;
                errorMessage = 'Total questions must be between 1 and 200';
            }
            break;
        case 'time_spent':
            if (value && (parseInt(value) < 0 || parseInt(value) > 600)) {
                isValid = false;
                errorMessage = 'Time spent must be between 0 and 600 seconds';
            }
            break;
        case 'test_name':
            if (value && value.length > 200) {
                isValid = false;
                errorMessage = 'Test name cannot exceed 200 characters';
            }
            break;
    }
    
    // Show/hide error
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    // Remove existing error
    clearFieldError(field);
    
    // Add error class
    field.classList.add('error');
    
    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#F44336';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '5px';
    
    // Insert after field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

// Clear field error
function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Show form errors summary
function showFormErrors(form) {
    const errorFields = form.querySelectorAll('.error');
    if (errorFields.length > 0) {
        const firstError = errorFields[0];
        firstError.focus();
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Show notification
        showNotification('Please fix the errors in the form', 'error');
    }
}

// Animations
function initializeAnimations() {
    // Animate cards on scroll
    observeElementsForAnimation();
    
    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Observe elements for animation
function observeElementsForAnimation() {
    const animateElements = document.querySelectorAll('.stat-card, .session-card, .question-card, .insight-card, .chart-container');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Initialize tooltips
function initializeTooltips() {
    // Simple tooltip implementation
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// Show tooltip
function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    if (!tooltipText) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 1000;
        pointer-events: none;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    element._tooltip = tooltip;
}

// Hide tooltip
function hideTooltip(event) {
    const element = event.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

// Local Storage Management
function initializeLocalStorage() {
    // Auto-save form data functionality is handled in the specific pages
    
    // Clear old auto-save data on successful form submission
    document.addEventListener('beforeunload', function() {
        // Clean up expired auto-save data (older than 24 hours)
        cleanupOldAutoSaveData();
    });
}

// Clean up old auto-save data
function cleanupOldAutoSaveData() {
    const now = Date.now();
    const dayInMs = 24 * 60 * 60 * 1000;
    
    for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('question_')) {
            const timestamp = localStorage.getItem(key + '_timestamp');
            if (timestamp && (now - parseInt(timestamp)) > dayInMs) {
                localStorage.removeItem(key);
                localStorage.removeItem(key + '_timestamp');
            }
        }
    }
}

// Utility Functions

// Fade out animation
function fadeOut(element, duration = 300) {
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = '0';
    
    setTimeout(() => {
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }, duration);
}

// Show notification
function showNotification(message, type = 'info', duration = 4000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;
    
    // Set background color based on type
    const colors = {
        success: '#4CAF50',
        error: '#F44336',
        warning: '#FF9800',
        info: '#2196F3'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-hide
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Format time duration
function formatDuration(seconds) {
    if (seconds < 60) {
        return `${seconds}s`;
    } else if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}m ${remainingSeconds}s`;
    } else {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    }
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Export functions for use in other scripts
window.TestAnalysisPlatform = {
    showNotification,
    formatNumber,
    formatDuration,
    fadeOut,
    debounce,
    throttle
};

// Service Worker Registration (for offline functionality)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(error) {
                console.log('ServiceWorker registration failed');
            });
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + Enter to submit forms
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const activeForm = document.querySelector('form:focus-within');
        if (activeForm) {
            const submitBtn = activeForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                e.preventDefault();
                submitBtn.click();
            }
        }
    }
    
    // Escape to close modals/notifications
    if (e.key === 'Escape') {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            notification.click();
        });
    }
});

// Performance monitoring
function measurePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    console.log(`Page load time: ${perfData.loadEventEnd - perfData.loadEventStart}ms`);
                }
            }, 0);
        });
    }
}

measurePerformance();
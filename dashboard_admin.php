<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}
include "db.php";

// Get gender statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=0 AND gender='Male'");
$stmt->execute();
$male_count = $stmt->get_result()->fetch_assoc()["total"];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=0 AND gender='Female'");
$stmt->execute();
$female_count = $stmt->get_result()->fetch_assoc()["total"];

// Get total seniors
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=0");
$stmt->execute();
$total_seniors = $stmt->get_result()->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            transition: margin-left 0.3s ease;
            padding-top: 60px; /* Account for fixed header */
        }
        
        .dashboard-container {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .dashboard-container.sidebar-open {
            margin-left: 220px;
        }
        
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0 !important; /* Always no margin on mobile */
                padding: 80px 15px 20px 15px; /* More top padding for header */
            }
            
            .gender-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .gender-card {
                padding: 20px;
            }
            
            .calendar-container {
                padding: 15px;
            }
            
            .calendar-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .calendar-nav {
                justify-content: center;
            }
            
            .calendar-grid {
                font-size: 14px;
            }
            
            .calendar-day {
                padding: 8px 4px;
                min-height: 35px;
                font-size: 12px;
            }
            
            .selected-date-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 15px;
            }
            
            .form-group input, .form-group textarea {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-container {
                padding: 60px 10px 20px 10px;
            }
            
            .calendar-day {
                padding: 6px 2px;
                min-height: 30px;
                font-size: 11px;
            }
            
            .calendar-day-header {
                padding: 8px 4px;
                font-size: 10px;
            }
            
            .gender-card {
                padding: 15px;
            }
            
            .gender-info .count {
                font-size: 24px;
            }
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            color: #333;
            margin: 0;
        }
        
        .dashboard-header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        
        .gender-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gender-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .gender-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .gender-card.male {
            background: linear-gradient(135deg, #fb923c, #f97316);
            color: white;
        }
        
        .gender-card.female {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: white;
        }

        .gender-card.all {
            background: linear-gradient(135deg, #0bab64, #3bb78f);
            color: white;
        }
        
        .gender-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .gender-info h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .gender-info .count {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }
        

        /* Calendar section */
        .calendar-section { margin-top: 30px; }
        
        /* Calendar UI */
        .calendar-container { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; margin-bottom:20px; }
        .calendar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .calendar-nav { display:flex; align-items:center; gap:15px; }
        .calendar-nav button { background:#f97316; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
        .calendar-nav button:hover { background:#1a0fcc; }
        .calendar-month-year { font-size:18px; font-weight:bold; color:#333; }
        .calendar-today-btn { background:#28a745; color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; }
        .calendar-today-btn:hover { background:#218838; }
        
        .calendar-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:1px; background:#ddd; border-radius:8px; overflow:hidden; }
        .calendar-day-header { background:#f8f9fa; padding:12px 8px; text-align:center; font-weight:bold; color:#666; font-size:12px; }
        .calendar-day { background:#fff; padding:12px 8px; text-align:center; cursor:pointer; transition:all 0.2s; min-height:40px; display:flex; align-items:center; justify-content:center; }
        .calendar-day:hover { background:#e3f2fd; }
        .calendar-day.other-month { background:#f5f5f5; color:#999; }
        .calendar-day.weekend { background:#f8f9fa; }
        .calendar-day.selected { background:#f97316; color:#fff; }
        .calendar-day.has-event { background:#fff3cd; border:2px solid #ffc107; }
        .calendar-day.has-event.selected { background:#f97316; color:#fff; }
        
        .selected-date-info { background:#e3f2fd; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; }
        .selected-date-info h3 { margin:0; color:#1976d2; }
        .add-event-btn { background:#f97316; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; }
        .add-event-btn:hover { background:#1a0fcc; }
        
        /* Modal */
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
        .modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:12px; width:90%; max-width:500px; position:relative; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-header h2 { margin:0; color:#333; }
        .close { color:#aaa; font-size:28px; font-weight:bold; cursor:pointer; }
        .close:hover { color:#000; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; font-weight:bold; color:#333; }
        .form-group input, .form-group textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box; }
        .form-group textarea { height:80px; resize:vertical; }
        .modal-buttons { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .modal-buttons button { padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
        .btn-primary { background:#f97316; color:#fff; }
        .btn-primary:hover { background:#1a0fcc; }
        .btn-secondary { background:#6c757d; color:#fff; }
        .btn-secondary:hover { background:#5a6268; }
        
        .events-list { background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:16px; }
        .events-list h3 { margin-top:0; }
        .event-item { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #eee; }
        .event-item:last-child { border-bottom:none; }
    </style>
    </head>
    <body>
        <?php include "admin_sidebar.php"; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?php echo $_SESSION["username"]; ?>!</p>
        </div>
        
        <div class="gender-stats">
            <div class="gender-card all" onclick="viewAllSeniors()">
                <div class="gender-icon"></div>
                <div class="gender-info">
                    <h3>Senior Citizens</h3>
                    <p class="count"><?php echo $total_seniors; ?></p>
                </div>
            </div>
            
            <div class="gender-card male" onclick="viewMaleSeniors()">
                <div class="gender-icon"></div>
                <div class="gender-info">
                    <h3>Male Seniors</h3>
                    <p class="count"><?php echo $male_count; ?></p>
                </div>
            </div>
            
            <div class="gender-card female" onclick="viewFemaleSeniors()">
                <div class="gender-icon"></div>
                <div class="gender-info">
                    <h3>Female Seniors</h3>
                    <p class="count"><?php echo $female_count; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-container calendar-section">
        <div class="dashboard-header">
            <h2>Senior Events Calendar</h2>
            <p>Create, edit, and manage events for senior citizens.</p>
        </div>

        <!-- Calendar Container -->
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button onclick="previousMonth()">&lt;</button>
                    <span class="calendar-month-year" id="currentMonthYear"></span>
                    <button onclick="nextMonth()">&gt;</button>
                </div>
                <button class="calendar-today-btn" onclick="goToToday()">Today</button>
            </div>
            
            <div class="calendar-grid" id="calendarGrid">
                <!-- Calendar will be generated by JavaScript -->
            </div>
        </div>

        <!-- Selected Date Info -->
        <div class="selected-date-info" id="selectedDateInfo" style="display:none;">
            <h3 id="selectedDateText"></h3>
            <button class="add-event-btn" onclick="openEventModal()">Add Event on This Day</button>
        </div>

        <!-- Events List -->
        <div class="events-list">
            <h3>Events for Selected Date</h3>
            <div id="eventsContainer">Select a date to view events</div>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Event</h2>
                <span class="close" onclick="closeEventModal()">&times;</span>
            </div>
            <form id="eventForm">
                <div class="form-group">
                    <label for="modalTitle">Event Title *</label>
                    <input type="text" id="modalEventTitle" required>
                </div>
                <div class="form-group">
                    <label for="modalStartDate">Start Date *</label>
                    <input type="date" id="modalStartDate" required>
                </div>
                <div class="form-group">
                    <label for="modalStartTime">Start Time *</label>
                    <input type="time" id="modalStartTime" required>
                </div>
                <div class="form-group">
                    <label for="modalEndDate">End Date (optional)</label>
                    <input type="date" id="modalEndDate">
                </div>
                <div class="form-group">
                    <label for="modalEndTime">End Time (optional)</label>
                    <input type="time" id="modalEndTime">
                </div>
                <div class="form-group">
                    <label for="modalDescription">Description</label>
                    <textarea id="modalDescription" placeholder="Event description..."></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeEventModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function viewMaleSeniors() {
            // Redirect to seniors page with male filter
            window.location.href = "seniors_admin.php?filter=male";
        }
        
        function viewFemaleSeniors() {
            // Redirect to seniors page with female filter
            window.location.href = "seniors_admin.php?filter=female";
        }

        function viewAllSeniors() {
            window.location.href = "seniors_view.php?filter=all";
        }
        
        // Calendar and Events Management
        let currentDate = new Date();
        let selectedDate = null;
        let allEvents = [];

        // Calendar Functions
        function generateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('currentMonthYear').textContent = 
                new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';
            
            // Day headers
            const dayHeaders = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            dayHeaders.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });
            
            // Calendar days
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = date.getDate();
                
                if (date.getMonth() !== month) {
                    dayElement.classList.add('other-month');
                }
                
                if (date.getDay() === 0 || date.getDay() === 6) {
                    dayElement.classList.add('weekend');
                }
                
                if (selectedDate && isSameDate(date, selectedDate)) {
                    dayElement.classList.add('selected');
                }
                
                // Check if this date has events
                const dateStr = formatDateForAPI(date);
                const hasEvents = allEvents.some(event => {
                    const eventDate = new Date(event.start);
                    return isSameDate(eventDate, date);
                });
                
                if (hasEvents) {
                    dayElement.classList.add('has-event');
                }
                
                dayElement.onclick = () => selectDate(date);
                calendarGrid.appendChild(dayElement);
            }
        }

        function selectDate(date) {
            selectedDate = new Date(date);
            generateCalendar();
            showSelectedDateInfo();
            loadEventsForDate();
        }

        function showSelectedDateInfo() {
            if (!selectedDate) {
                document.getElementById('selectedDateInfo').style.display = 'none';
                return;
            }
            
            const dateStr = selectedDate.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('selectedDateText').textContent = dateStr;
            document.getElementById('selectedDateInfo').style.display = 'flex';
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar();
        }

        function goToToday() {
            currentDate = new Date();
            selectedDate = new Date();
            generateCalendar();
            showSelectedDateInfo();
            loadEventsForDate();
        }

        // Event Management
        async function loadAllEvents() {
            try {
                const res = await fetch('events_api.php');
                allEvents = await res.json();
                generateCalendar();
            } catch (e) {
                console.error('Error loading events:', e);
            }
        }

        async function loadEventsForDate() {
            if (!selectedDate) {
                document.getElementById('eventsContainer').innerHTML = 'Select a date to view events';
                return;
            }
            
            const dateStr = formatDateForAPI(selectedDate);
            const dayEvents = allEvents.filter(event => {
                const eventDate = new Date(event.start);
                return isSameDate(eventDate, selectedDate);
            });
            
            const container = document.getElementById('eventsContainer');
            if (dayEvents.length === 0) {
                container.innerHTML = '<p>No events for this date.</p>';
                return;
            }
            
            container.innerHTML = dayEvents.map(ev => `
                <div class="event-item">
                    <div>
                        <strong>${escapeHtml(ev.title)}</strong><br/>
                        <small>${formatTime(ev.start)}${ev.end ? ' - ' + formatTime(ev.end) : ''}</small><br/>
                        ${ev.description ? '<small>' + escapeHtml(ev.description) + '</small>' : ''}
                    </div>
                    <div>
                        <button class="secondary" onclick="editEvent(${ev.id})">Edit</button>
                        <button onclick="deleteEvent(${ev.id})">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        // Modal Functions
        function openEventModal() {
            if (!selectedDate) return;
            
            document.getElementById('modalEventTitle').value = '';
            document.getElementById('modalStartDate').value = formatDateForInput(selectedDate);
            document.getElementById('modalStartTime').value = '09:00';
            document.getElementById('modalEndDate').value = '';
            document.getElementById('modalEndTime').value = '';
            document.getElementById('modalDescription').value = '';
            
            document.getElementById('eventModal').style.display = 'block';
        }

        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        // Event Form Submission
        document.getElementById('eventForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const title = document.getElementById('modalEventTitle').value.trim();
            const startDate = document.getElementById('modalStartDate').value;
            const startTime = document.getElementById('modalStartTime').value;
            const endDate = document.getElementById('modalEndDate').value;
            const endTime = document.getElementById('modalEndTime').value;
            const description = document.getElementById('modalDescription').value.trim();
            
            if (!title || !startDate || !startTime) {
                alert('Title, start date, and start time are required');
                return;
            }
            
            const start = startDate + 'T' + startTime + ':00';
            const end = (endDate && endTime) ? endDate + 'T' + endTime + ':00' : null;
            
            try {
                await fetch('events_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'create', 
                        title, 
                        start, 
                        end, 
                        description: description || null 
                    })
                });
                
                closeEventModal();
                await loadAllEvents();
                loadEventsForDate();
            } catch (e) {
                alert('Error creating event');
            }
        });

        async function editEvent(id) {
            const event = allEvents.find(e => e.id == id);
            if (!event) return;
            
            const title = prompt('New title (leave blank to keep)', event.title);
            if (title === null) return;
            
            const start = prompt('New start (YYYY-MM-DDTHH:MM) or blank', event.start.substring(0, 16));
            const end = prompt('New end (YYYY-MM-DDTHH:MM) or blank', event.end ? event.end.substring(0, 16) : '');
            const description = prompt('New description or blank', event.description || '');
            
            try {
                await fetch('events_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'update', 
                        id, 
                        title: title || null, 
                        start: start || null, 
                        end: end || null, 
                        description: description || null 
                    })
                });
                
                await loadAllEvents();
                loadEventsForDate();
            } catch (e) {
                alert('Error updating event');
            }
        }

        async function deleteEvent(id) {
            if (!confirm('Delete this event?')) return;
            
            try {
                await fetch('events_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                
                await loadAllEvents();
                loadEventsForDate();
            } catch (e) {
                alert('Error deleting event');
            }
        }

        // Utility Functions
        function isSameDate(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        }

        function formatDateForAPI(date) {
            return date.toISOString().split('T')[0];
        }

        function formatDateForInput(date) {
            return date.toISOString().split('T')[0];
        }

        function formatTime(isoString) {
            try {
                return new Date(isoString).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            } catch (e) {
                return isoString;
            }
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target === modal) {
                closeEventModal();
            }
        }

        // Sidebar Toggle Function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const dashboardContainer = document.querySelector('.dashboard-container');
            
            sidebar.classList.toggle('show');
            
            // Adjust content margin based on sidebar state
            if (window.innerWidth > 768) {
                dashboardContainer.classList.toggle('sidebar-open');
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const headerBurger = document.querySelector('.header-burger');
            const dashboardContainer = document.querySelector('.dashboard-container');
            
            if (!sidebar.contains(event.target) && 
                !headerBurger.contains(event.target)) {
                sidebar.classList.remove('show');
                
                // Adjust content margin
                if (window.innerWidth > 768) {
                    dashboardContainer.classList.remove('sidebar-open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const dashboardContainer = document.querySelector('.dashboard-container');
            
            if (window.innerWidth <= 768) {
                // On mobile, always remove margin
                dashboardContainer.classList.remove('sidebar-open');
            } else if (sidebar.classList.contains('show')) {
                // On desktop, add margin if sidebar is open
                dashboardContainer.classList.add('sidebar-open');
            }
        });

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            generateCalendar();
            loadAllEvents();
        });
    </script>
</body>
</html>

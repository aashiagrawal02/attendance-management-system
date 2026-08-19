/* ===========================
   EduTrack — app.js
   All logic lives here.
   Data stored in localStorage
=========================== */

// ---- DATA STORE ----
// We use localStorage as our "database" (browser-based storage)
// Keys:
//   "students"   → array of student objects
//   "attendance" → object { "YYYY-MM-DD": { rollNo: "Present"|"Absent"|"Leave" } }

function getStudents() {
  return JSON.parse(localStorage.getItem("students") || "[]");
}

function saveStudents(arr) {
  localStorage.setItem("students", JSON.stringify(arr));
}

function getAttendance() {
  return JSON.parse(localStorage.getItem("attendance") || "{}");
}

function saveAttendanceData(obj) {
  localStorage.setItem("attendance", JSON.stringify(obj));
}

// ---- NAVIGATION ----
function showPage(name) {
  document.querySelectorAll(".page").forEach(p => p.classList.remove("active"));
  document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));

  document.getElementById("page-" + name).classList.add("active");

  const titles = { dashboard: "Dashboard", students: "Students", attendance: "Mark Attendance", reports: "Reports" };
  document.getElementById("pageTitle").textContent = titles[name];

  // Find and activate nav item
  document.querySelectorAll(".nav-item").forEach(item => {
    if (item.getAttribute("onclick") && item.getAttribute("onclick").includes(name)) {
      item.classList.add("active");
    }
  });

  if (name === "dashboard") refreshDashboard();
  if (name === "attendance") initAttendancePage();
  if (name === "reports") initReportsPage();
  if (name === "students") renderStudentTable();

  closeSidebarOnMobile();
}

function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("open");
}

function closeSidebarOnMobile() {
  if (window.innerWidth <= 768) {
    document.getElementById("sidebar").classList.remove("open");
  }
}

// ---- DATE ----
function todayStr() {
  return new Date().toISOString().split("T")[0];
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr + "T00:00:00");
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
}

// ---- TOAST ----
function showToast(msg, duration = 3000) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), duration);
}

// ---- STUDENT FUNCTIONS ----
function addStudent() {
  const rollNo = document.getElementById("rollNo").value.trim();
  const name = document.getElementById("stuName").value.trim();
  const cls = document.getElementById("stuClass").value.trim();

  if (!rollNo || !name || !cls) {
    showToast("⚠️ Please fill all fields.");
    return;
  }

  const students = getStudents();
  if (students.find(s => s.rollNo === rollNo)) {
    showToast("⚠️ Roll number already exists!");
    return;
  }

  students.push({ rollNo, name, cls });
  saveStudents(students);

  // Clear fields
  document.getElementById("rollNo").value = "";
  document.getElementById("stuName").value = "";
  document.getElementById("stuClass").value = "";

  renderStudentTable();
  updateClassFilters();
  showToast("✅ Student added successfully!");
}

function deleteStudent(rollNo) {
  if (!confirm("Delete this student? Their attendance data will also be removed.")) return;

  let students = getStudents();
  students = students.filter(s => s.rollNo !== rollNo);
  saveStudents(students);

  // Remove from attendance records too
  let att = getAttendance();
  for (let date in att) {
    delete att[date][rollNo];
  }
  saveAttendanceData(att);

  renderStudentTable();
  updateClassFilters();
  showToast("🗑️ Student removed.");
}

function renderStudentTable(filter = "") {
  const students = getStudents();
  const att = getAttendance();
  const tbody = document.getElementById("studentTableBody");

  const filtered = filter
    ? students.filter(s => s.name.toLowerCase().includes(filter.toLowerCase()) || s.rollNo.includes(filter))
    : students;

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="empty-row">No students found.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(s => {
    let present = 0, absent = 0;
    for (let date in att) {
      if (att[date][s.rollNo] === "Present") present++;
      else if (att[date][s.rollNo] === "Absent") absent++;
    }
    return `<tr>
      <td><span style="font-family:var(--mono);font-weight:600;">${s.rollNo}</span></td>
      <td>${s.name}</td>
      <td><span class="badge" style="background:#eef0ff;color:#3b5bdb;">${s.cls}</span></td>
      <td style="color:var(--success);font-weight:600;">${present}</td>
      <td style="color:var(--danger);font-weight:600;">${absent}</td>
      <td><button class="btn btn-ghost btn-sm" onclick="deleteStudent('${s.rollNo}')">🗑 Remove</button></td>
    </tr>`;
  }).join("");
}

function filterStudents() {
  const q = document.getElementById("studentSearch").value;
  renderStudentTable(q);
}

// ---- CLASS FILTER DROPDOWNS ----
function updateClassFilters() {
  const students = getStudents();
  const classes = [...new Set(students.map(s => s.cls))].sort();

  ["classFilter", "reportClass"].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const prev = el.value;
    el.innerHTML = `<option value="">All Classes</option>` +
      classes.map(c => `<option value="${c}" ${c === prev ? "selected" : ""}>${c}</option>`).join("");
  });
}

// ---- ATTENDANCE PAGE ----
function initAttendancePage() {
  const dateInput = document.getElementById("attendanceDate");
  if (!dateInput.value) dateInput.value = todayStr();
  updateClassFilters();
  renderAttendanceTable();
  dateInput.addEventListener("change", renderAttendanceTable);
}

function renderAttendanceTable() {
  const date = document.getElementById("attendanceDate").value;
  const classVal = document.getElementById("classFilter").value;
  const att = getAttendance();
  let students = getStudents();

  if (classVal) students = students.filter(s => s.cls === classVal);

  const today = att[date] || {};
  const tbody = document.getElementById("attendanceTableBody");

  if (students.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="empty-row">No students found. Add students first.</td></tr>`;
    return;
  }

  tbody.innerHTML = students.map(s => {
    const currentStatus = today[s.rollNo] || "Present";
    return `<tr>
      <td><span style="font-family:var(--mono);font-weight:600;">${s.rollNo}</span></td>
      <td>${s.name}</td>
      <td><span class="badge" style="background:#eef0ff;color:#3b5bdb;">${s.cls}</span></td>
      <td>
        <div class="status-selector" id="sel-${s.rollNo}">
          <button class="status-btn ${currentStatus === 'Present' ? 'active-present' : ''}"
            onclick="setStatus('${s.rollNo}', 'Present')">P</button>
          <button class="status-btn ${currentStatus === 'Absent' ? 'active-absent' : ''}"
            onclick="setStatus('${s.rollNo}', 'Absent')">A</button>
          <button class="status-btn ${currentStatus === 'Leave' ? 'active-leave' : ''}"
            onclick="setStatus('${s.rollNo}', 'Leave')">L</button>
        </div>
      </td>
    </tr>`;
  }).join("");
}

function setStatus(rollNo, status) {
  const sel = document.getElementById("sel-" + rollNo);
  if (!sel) return;
  sel.querySelectorAll(".status-btn").forEach(b => {
    b.classList.remove("active-present", "active-absent", "active-leave");
  });
  const idx = { Present: 0, Absent: 1, Leave: 2 }[status];
  sel.children[idx].classList.add("active-" + status.toLowerCase());
}

function markAll(status) {
  document.querySelectorAll(".status-selector").forEach(sel => {
    const rollNo = sel.id.replace("sel-", "");
    setStatus(rollNo, status);
  });
}

function getCurrentAttendanceSelections() {
  const result = {};
  document.querySelectorAll(".status-selector").forEach(sel => {
    const rollNo = sel.id.replace("sel-", "");
    const activeBtn = sel.querySelector(".status-btn.active-present") ||
                      sel.querySelector(".status-btn.active-absent") ||
                      sel.querySelector(".status-btn.active-leave");
    if (activeBtn) {
      if (activeBtn.classList.contains("active-present")) result[rollNo] = "Present";
      else if (activeBtn.classList.contains("active-absent")) result[rollNo] = "Absent";
      else result[rollNo] = "Leave";
    }
  });
  return result;
}

function saveAttendance() {
  const date = document.getElementById("attendanceDate").value;
  if (!date) { showToast("⚠️ Please select a date."); return; }

  const selections = getCurrentAttendanceSelections();
  if (Object.keys(selections).length === 0) {
    showToast("⚠️ No students to save.");
    return;
  }

  const att = getAttendance();
  att[date] = { ...(att[date] || {}), ...selections };
  saveAttendanceData(att);
  showToast(`✅ Attendance saved for ${formatDate(date)}!`);
}

// ---- DASHBOARD ----
function refreshDashboard() {
  const students = getStudents();
  const att = getAttendance();
  const today = att[todayStr()] || {};

  const total = students.length;
  let present = 0, absent = 0;
  students.forEach(s => {
    if (today[s.rollNo] === "Present") present++;
    else if (today[s.rollNo] === "Absent") absent++;
  });

  document.getElementById("stat-total").textContent = total;
  document.getElementById("stat-present").textContent = present;
  document.getElementById("stat-absent").textContent = absent;
  const rate = total > 0 ? Math.round((present / total) * 100) : 0;
  document.getElementById("stat-rate").textContent = rate + "%";

  // Table
  const tbody = document.getElementById("dashboardBody");
  if (students.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="empty-row">No students added yet.</td></tr>`;
  } else {
    tbody.innerHTML = students.slice(0, 10).map(s => {
      const status = today[s.rollNo] || "—";
      let badge = `<span style="color:var(--text-hint);">—</span>`;
      if (status === "Present") badge = `<span class="badge badge-present">✓ Present</span>`;
      else if (status === "Absent") badge = `<span class="badge badge-absent">✗ Absent</span>`;
      else if (status === "Leave") badge = `<span class="badge badge-leave">Leave</span>`;
      return `<tr>
        <td><span style="font-family:var(--mono);font-weight:600;">${s.rollNo}</span></td>
        <td>${s.name}</td>
        <td>${s.cls}</td>
        <td>${badge}</td>
      </tr>`;
    }).join("");
  }

  // Bar chart (per class)
  renderBarChart(students, today);
}

function renderBarChart(students, today) {
  const classMap = {};
  students.forEach(s => {
    if (!classMap[s.cls]) classMap[s.cls] = { present: 0, total: 0 };
    classMap[s.cls].total++;
    if (today[s.rollNo] === "Present") classMap[s.cls].present++;
  });

  const container = document.getElementById("barChart");
  const classes = Object.keys(classMap);

  if (classes.length === 0) {
    container.innerHTML = `<p style="color:var(--text-hint);font-size:13px;width:100%;text-align:center;">No data to display.</p>`;
    return;
  }

  const maxVal = Math.max(...classes.map(c => classMap[c].total), 1);

  container.innerHTML = classes.map(cls => {
    const { present, total } = classMap[cls];
    const pct = Math.round((present / total) * 100);
    const heightPx = Math.round((present / maxVal) * 110);
    const color = pct >= 75 ? "#2f9e44" : pct >= 50 ? "#e67700" : "#e03131";
    return `<div class="bar-group">
      <div class="bar-val">${pct}%</div>
      <div class="bar" style="height:${heightPx}px;background:${color};"></div>
      <div class="bar-label">${cls}</div>
    </div>`;
  }).join("");
}

// ---- REPORTS ----
function initReportsPage() {
  updateClassFilters();
  document.getElementById("reportOutput").style.display = "none";

  const today = todayStr();
  const firstOfMonth = today.slice(0, 8) + "01";
  document.getElementById("reportFrom").value = firstOfMonth;
  document.getElementById("reportTo").value = today;
}

function generateReport() {
  const from = document.getElementById("reportFrom").value;
  const to = document.getElementById("reportTo").value;
  const cls = document.getElementById("reportClass").value;

  if (!from || !to) { showToast("⚠️ Please select both dates."); return; }
  if (from > to) { showToast("⚠️ 'From' date must be before 'To' date."); return; }

  let students = getStudents();
  if (cls) students = students.filter(s => s.cls === cls);

  const att = getAttendance();

  // Get all dates in range
  const dates = [];
  let cur = new Date(from + "T00:00:00");
  const end = new Date(to + "T00:00:00");
  while (cur <= end) {
    dates.push(cur.toISOString().split("T")[0]);
    cur.setDate(cur.getDate() + 1);
  }

  if (students.length === 0) { showToast("⚠️ No students found for this filter."); return; }

  // Build report data
  const reportData = students.map(s => {
    let present = 0, absent = 0, leave = 0;
    dates.forEach(d => {
      const status = att[d] && att[d][s.rollNo];
      if (status === "Present") present++;
      else if (status === "Absent") absent++;
      else if (status === "Leave") leave++;
    });
    const recorded = present + absent + leave;
    const pct = recorded > 0 ? Math.round((present / recorded) * 100) : 0;
    return { ...s, present, absent, leave, recorded, pct };
  });

  // Summary stats
  const totalDays = dates.length;
  const avgPct = reportData.length > 0
    ? Math.round(reportData.reduce((a, r) => a + r.pct, 0) / reportData.length)
    : 0;
  const below75 = reportData.filter(r => r.pct < 75 && r.recorded > 0).length;

  document.getElementById("reportStats").innerHTML = `
    <div class="stat-card">
      <div class="stat-icon" style="background:#e8ecff;">📅</div>
      <div class="stat-info"><div class="stat-number">${totalDays}</div><div class="stat-label">Days in Range</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#e6f7ee;">👥</div>
      <div class="stat-info"><div class="stat-number">${students.length}</div><div class="stat-label">Students</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f3e8ff;">📈</div>
      <div class="stat-info"><div class="stat-number">${avgPct}%</div><div class="stat-label">Avg Attendance</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fff5f5;">⚠️</div>
      <div class="stat-info"><div class="stat-number">${below75}</div><div class="stat-label">Below 75%</div></div>
    </div>
  `;

  const tbody = document.getElementById("reportTableBody");
  tbody.innerHTML = reportData.map(r => {
    const pctClass = r.pct >= 75 ? "pct-high" : r.pct >= 50 ? "pct-medium" : "pct-low";
    const pctDisplay = r.recorded === 0 ? `<span style="color:var(--text-hint);">No record</span>` : `<span class="${pctClass}">${r.pct}%</span>`;
    return `<tr>
      <td><span style="font-family:var(--mono);font-weight:600;">${r.rollNo}</span></td>
      <td>${r.name}</td>
      <td><span class="badge" style="background:#eef0ff;color:#3b5bdb;">${r.cls}</span></td>
      <td style="color:var(--success);font-weight:600;">${r.present}</td>
      <td style="color:var(--danger);font-weight:600;">${r.absent}</td>
      <td>${pctDisplay}</td>
    </tr>`;
  }).join("");

  document.getElementById("reportOutput").style.display = "block";
}

// ---- INIT ----
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("todayDate").textContent = formatDate(todayStr());
  updateClassFilters();
  refreshDashboard();

  // Add sample students if empty (helps beginners see it working)
  if (getStudents().length === 0) {
    const samples = [
      { rollNo: "101", name: "Aryan Sharma", cls: "10-A" },
      { rollNo: "102", name: "Priya Patel",  cls: "10-A" },
      { rollNo: "103", name: "Rohit Verma",  cls: "10-B" },
      { rollNo: "104", name: "Sneha Joshi",  cls: "10-B" },
      { rollNo: "105", name: "Karan Mehta",  cls: "11-A" },
    ];
    saveStudents(samples);
    updateClassFilters();
    refreshDashboard();
    renderStudentTable();
  }
});

<?php
// ملف البيانات
$dataFile = __DIR__ . "/data.txt";

// إذا الملف مش موجود اعمله
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// قراءة البيانات
$data = json_decode(file_get_contents($dataFile), true);

// حفظ البيانات لو تم الإرسال عبر AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $month = $_POST['month'];
    $year = $_POST['year'];
    $day = $_POST['day'];
    $status = $_POST['status'];
    $comment = trim($_POST['comment']);

    if (!isset($data[$year])) {
        $data[$year] = [];
    }

    if (!isset($data[$year][$month])) {
        $data[$year][$month] = [];
    }

    $data[$year][$month][$day] = [
        'status' => $status,
        'comment' => $comment
    ];

    file_put_contents($dataFile, json_encode($data));

    // حساب الإحصائيات لجميع الأشهر
    $totalDoneAll = 0;
    $totalNotDoneAll = 0;
    
    // حساب سلسلة الأيام الناجحة المتتالية
    $currentStreak = 0;
    $maxStreak = 0;
    $tempStreak = 0;
    
    // جمع جميع الأيام مرتبة حسب التاريخ
    $allDays = [];
    foreach ($data as $y => $months) {
        foreach ($months as $m => $days) {
            foreach ($days as $d => $dayData) {
                $timestamp = strtotime("$y-$m-$d");
                $allDays[$timestamp] = $dayData;
            }
        }
    }
    
    // ترتيب الأيام من الأقدم إلى الأحدث
    ksort($allDays);
    
    // حساب السلاسل
    foreach ($allDays as $dayData) {
        if ($dayData['status'] === "done") {
            $totalNotDoneAll++;
            $tempStreak++;
            if ($tempStreak > $maxStreak) {
                $maxStreak = $tempStreak;
            }
        } elseif ($dayData['status'] === "fail") {
            $totalDoneAll++;
            $tempStreak = 0;
        }
    }
    
    // السلسلة الحالية هي آخر سلسلة
    $currentStreak = $tempStreak;

    $totalAll = $totalDoneAll + $totalNotDoneAll;
    $percentAll = $totalAll > 0 ? round(($totalNotDoneAll / $totalAll) * 100, 2) : 0;

    echo json_encode([
        "success" => true,
        "totalDoneAll" => $totalDoneAll,
        "totalNotDoneAll" => $totalNotDoneAll,
        "totalDaysAll" => $totalAll,
        "percentAll" => $percentAll,
        "currentStreak" => $currentStreak,
        "maxStreak" => $maxStreak
    ]);
    exit;
}

// تحديد الشهر والسنة الحاليين
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// حساب عدد الأيام في الشهر الحالي
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// حساب الإحصائيات لجميع الأشهر
$totalDoneAll = 0;
$totalNotDoneAll = 0;
    
// حساب سلسلة الأيام الناجحة المتتالية
$currentStreak = 0;
$maxStreak = 0;
$tempStreak = 0;

// جمع جميع الأيام مرتبة حسب التاريخ
$allDays = [];
foreach ($data as $y => $months) {
    foreach ($months as $m => $days) {
        foreach ($days as $d => $dayData) {
            $timestamp = strtotime("$y-$m-$d");
            $allDays[$timestamp] = $dayData;
        }
    }
}

// ترتيب الأيام من الأقدم إلى الأحدث
ksort($allDays);

// حساب السلاسل
foreach ($allDays as $dayData) {
    if ($dayData['status'] === "done") {
        $totalNotDoneAll++;
        $tempStreak++;
        if ($tempStreak > $maxStreak) {
            $maxStreak = $tempStreak;
        }
    } elseif ($dayData['status'] === "fail") {
        $totalDoneAll++;
        $tempStreak = 0;
    }
}

// السلسلة الحالية هي آخر سلسلة
$currentStreak = $tempStreak;

$totalAll = $totalDoneAll + $totalNotDoneAll;
$percentAll = $totalAll > 0 ? round(($totalNotDoneAll / $totalAll) * 100, 2) : 0;

// العثور على آخر يوم محدد في الشهر الحالي
$lastSetDay = 0;
if (isset($data[$year][$month])) {
    for ($day = $daysInMonth; $day >= 1; $day--) {
        if (isset($data[$year][$month][$day]) && !empty($data[$year][$month][$day]['status'])) {
            $lastSetDay = $day;
            break;
        }
    }
}

// تحديد اليوم الذي يجب التمرير إليه (اليوم التالي لآخر يوم محدد)
$scrollToDay = ($lastSetDay < $daysInMonth) ? $lastSetDay + 1 : $lastSetDay;
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موقع ترك العادة السرية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding: 15px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), #2980b9);
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
        }
        
        header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--success), var(--primary), var(--warning));
        }
        
        h1 {
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 32px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
        
        .subtitle {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .filters {
            padding: 20px;
            background-color: var(--light);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .filters input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            width: 100px;
            text-align: center;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .filters input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        button i {
            font-size: 14px;
        }
        
        .calendar {
            overflow-x: auto;
            padding: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th {
            background: linear-gradient(135deg, var(--dark), #34495e);
            color: white;
            padding: 15px;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 12px;
            border: 1px solid #e0e0e0;
            text-align: center;
            transition: background 0.2s;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #f1f8ff;
        }
        
        .status-options {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .status-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .status-label:hover {
            transform: scale(1.05);
        }
        
        .done {
            color: var(--success);
            border-color: var(--success);
            background-color: rgba(46, 204, 113, 0.1);
        }
        
        .done input[type="radio"]:checked ~ span::before {
            content: "✓ ";
        }
        
        .fail {
            color: var(--danger);
            border-color: var(--danger);
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        input[type="radio"] {
            margin-left: 8px;
            cursor: pointer;
            transform: scale(1.2);
        }
        
        .comment {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s;
        }
        
        .comment:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .saved {
            color: var(--success);
            font-size: 14px;
            display: none;
            margin-top: 5px;
            font-weight: bold;
        }
        
        .stats {
            background: linear-gradient(135deg, var(--dark), #34495e);
            color: white;
            padding: 25px 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            display: block;
            margin-top: 10px;
        }
        
        .history {
            padding: 25px 20px;
            background-color: var(--light);
        }
        
        .history h3 {
            margin-bottom: 20px;
            text-align: center;
            color: var(--dark);
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .history-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }
        
        .history-btn {
            background-color: var(--dark);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .history-btn:hover {
            background-color: #1e2a36;
            transform: translateY(-2px);
        }
        
        .motivation {
            text-align: center;
            padding: 15px;
            margin: 15px 0;
            background: linear-gradient(135deg, #fff6e6, #ffefd5);
            border-radius: 10px;
            border-right: 4px solid var(--warning);
        }
        
        .motivation i {
            color: var(--warning);
            margin-left: 8px;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--primary));
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .status-options {
                flex-direction: column;
                gap: 8px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 10px 5px;
            }
        }
        
        .highlight-row {
            background-color: #fff9c4 !important;
            animation: pulse 2s infinite;
            border: 2px solid #ffd54f !important;
        }
        
        @keyframes pulse {
            0% { background-color: #fff9c4; }
            50% { background-color: #fff176; }
            100% { background-color: #fff9c4; }
        }
        
        .today {
            background-color: #e3f2fd !important;
            border-right: 4px solid var(--primary) !important;
        }
        
        .streak-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        
        .streak-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--shadow);
            min-width: 150px;
            border-left: 4px solid var(--success);
        }
        
        .streak-box.max {
            border-left-color: var(--primary);
        }
        
        .streak-title {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .streak-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark);
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1><i class="fas fa-seedling"></i> موقع ترك العادة السرية</h1>
        <p class="subtitle">تتبع تقدمك اليومي نحو حياة أكثر صحة وإيجابية وكن سيد إرادتك</p>
    </header>

    <div class="filters">
        <form method="get" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <span>الشهر:</span>
            <input type="number" name="month" value="<?= $month ?>" min="1" max="12">
            <span>السنة:</span>
            <input type="number" name="year" value="<?= $year ?>" min="2000" max="2100">
            <button type="submit"><i class="fas fa-calendar-alt"></i> عرض التاريخ</button>
        </form>
    </div>

    <div class="motivation">
        <i class="fas fa-quote-left"></i>
        <strong>تذكر:</strong> كل يوم تمر فيه بدون عادة سلبية هو انتصار جديد وإضافة إلى قوة إرادتك
        <i class="fas fa-quote-right"></i>
    </div>

    <div class="calendar">
        <table>
            <tr>
                <th>اليوم</th>
                <th>الحالة</th>
                <th>تعليق</th>
            </tr>
            <?php for ($day = 1; $day <= $daysInMonth; $day++): 
                $current = $data[$year][$month][$day] ?? ['status' => '', 'comment' => ''];
                $rowClass = ($day == $scrollToDay) ? 'highlight-row' : '';
                // إذا كان اليوم الحالي، أضف كلاس today
                if ($day == date('j') && $month == date('m') && $year == date('Y')) {
                    $rowClass .= ' today';
                }
            ?>
            <tr id="day-<?= $day ?>" class="<?= trim($rowClass) ?>">
                <td><strong><?= $day ?></strong></td>
                <td>
                    <div class="status-options">
                        <label class="status-label done">
                            <input type="radio" name="status<?= $day ?>" value="done" 
                                <?= $current['status'] === 'done' ? 'checked' : '' ?>
                                onchange="saveData(<?= $day ?>, event)">
                            <span>✅ لم أفعل</span>
                        </label>
                        <label class="status-label fail">
                            <input type="radio" name="status<?= $day ?>" value="fail" 
                                <?= $current['status'] === 'fail' ? 'checked' : '' ?>
                                onchange="saveData(<?= $day ?>, event)">
                            <span>❌ فعلت</span>
                        </label>
                    </div>
                    <span id="saved<?= $day ?>" class="saved"><i class="fas fa-check-circle"></i> تم الحفظ</span>
                </td>
                <td>
                    <input type="text" class="comment" id="comment<?= $day ?>" placeholder="اكتب تعليقك هنا..." 
                           value="<?= htmlspecialchars($current['comment']) ?>" 
                           oninput="saveData(<?= $day ?>, event)">
                </td>
            </tr>
            <?php endfor; ?>
        </table>
    </div>

    <div class="progress-bar">
        <div class="progress" id="progressBar" style="width: <?php echo $totalAll > 0 ? (($totalNotDoneAll / $totalAll) * 100) : 0; ?>%"></div>
    </div>

    <div class="stats" id="statsBox">
        <div class="stat-box">
            <span><i class="fas fa-check-circle"></i> الأيام الناجحة</span>
            <span class="stat-number" id="totalNotDoneAll"><?= $totalNotDoneAll ?></span>
        </div>
        <div class="stat-box">
            <span><i class="fas fa-times-circle"></i> الأيام غير الناجحة</span>
            <span class="stat-number" id="totalDoneAll"><?= $totalDoneAll ?></span>
        </div>
        <div class="stat-box">
            <span><i class="fas fa-calendar"></i> إجمالي الأيام</span>
            <span class="stat-number" id="totalDaysAll"><?= $totalAll ?></span>
        </div>
        <div class="stat-box">
            <span><i class="fas fa-chart-line"></i> نسبة الالتزام</span>
            <span class="stat-number" id="percentAll">
                <?php echo $totalAll > 0 ? round(($totalNotDoneAll / $totalAll) * 100, 2) . '%' : '0%'; ?>
            </span>
        </div>
    </div>

    <div class="streak-info">
        <div class="streak-box">
            <div class="streak-title">السلسلة الحالية</div>
            <div class="streak-value" id="currentStreak"><?= $currentStreak ?> يوم</div>
        </div>
        <div class="streak-box max">
            <div class="streak-title">أطول سلسلة</div>
            <div class="streak-value" id="maxStreak"><?= $maxStreak ?> يوم</div>
        </div>
    </div>

    <div class="history">
        <h3><i class="fas fa-history"></i> السجلات السابقة</h3>
        <div class="history-buttons">
            <?php foreach ($data as $y => $months): ?>
                <?php foreach ($months as $m => $days): ?>
                    <button class="history-btn" onclick="location.href='?month=<?= $m ?>&year=<?= $y ?>'">
                        <i class="fas fa-calendar"></i> شهر <?= $m ?> / سنة <?= $y ?>
                    </button>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// الانتقال إلى اليوم التالي لآخر يوم محدد عند تحميل الصفحة
window.onload = function() {
    const scrollToDay = <?= $scrollToDay ?>;
    if (scrollToDay > 0) {
        const element = document.getElementById('day-' + scrollToDay);
        if (element) {
            // تأخير بسيط لضمان تحميل الصفحة بالكامل أولاً
            setTimeout(() => {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }
    }
};

function saveData(day, event) {
    // منع أي سلوك افتراضي للحدث (مثل إعادة تحميل الصفحة)
    if (event) {
        event.preventDefault();
    }
    
    const status = document.querySelector(`input[name="status${day}"]:checked`)?.value || '';
    const comment = document.getElementById(`comment${day}`).value;

    const formData = new FormData();
    formData.append("ajax", "1");
    formData.append("day", day);
    formData.append("status", status);
    formData.append("comment", comment);
    formData.append("month", <?= $month ?>);
    formData.append("year", <?= $year ?>);

    fetch("index.php", {
        method: "POST",
        body: formData
    }).then(res => res.json())
      .then(data => {
          const savedMsg = document.getElementById(`saved${day}`);
          savedMsg.style.display = "inline";
          setTimeout(() => savedMsg.style.display = "none", 2000);

          // تحديث لوحة الإحصائيات مباشرة
          document.getElementById("totalNotDoneAll").textContent = data.totalNotDoneAll;
          document.getElementById("totalDoneAll").textContent = data.totalDoneAll;
          document.getElementById("totalDaysAll").textContent = data.totalDaysAll;
          document.getElementById("percentAll").textContent = data.percentAll + "%";
          document.getElementById("currentStreak").textContent = data.currentStreak + " يوم";
          document.getElementById("maxStreak").textContent = data.maxStreak + " يوم";
          
          // تحديث شريط التقدم
          const progressBar = document.getElementById("progressBar");
          const progressPercent = data.totalDaysAll > 0 ? (data.totalNotDoneAll / data.totalDaysAll) * 100 : 0;
          progressBar.style.width = progressPercent + "%";
          
          // إزالة التمييز عن الصف الحالي إذا تم تحديده
          const currentRow = document.getElementById(`day-${day}`);
          currentRow.classList.remove('highlight-row');
          
          // البحث عن الصف التالي غير المحدد وتمييزه
          let nextUnsetDay = day + 1;
          const daysInMonth = <?= $daysInMonth ?>;
          
          while (nextUnsetDay <= daysInMonth) {
              const nextStatus = document.querySelector(`input[name="status${nextUnsetDay}"]:checked`);
              if (!nextStatus || nextStatus.value === '') {
                  const nextRow = document.getElementById(`day-${nextUnsetDay}`);
                  if (nextRow) {
                      nextRow.classList.add('highlight-row');
                      // الانتقال إلى الصف التالي بعد حفظ البيانات
                      setTimeout(() => {
                          nextRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                      }, 500);
                  }
                  break;
              }
              nextUnsetDay++;
          }
      });
}
</script>
</body>
</html>
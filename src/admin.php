<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$username = $_SESSION['username'];
$loginTime = date('Y-m-d H:i:s', $_SESSION['login_time']);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - HCAMP 2025</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: #e1e1e1;
            min-height: 100vh;
        }

        .header {
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        .user-info {
            color: #b1b1b1;
            font-size: 14px;
        }

        .admin-badge {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-section {
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 32px;
            text-align: center;
        }

        .welcome-section h2 {
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .welcome-section p {
            font-size: 16px;
            color: #b1b1b1;
            line-height: 1.6;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .dashboard-card {
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            transition: all 0.2s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .card-content {
            color: #b1b1b1;
            line-height: 1.6;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #4a9eff;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .logout-btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135div, #dc2626 0%, #b91c1c 100%);
            border: none;
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1>HCAMP 2025</h1>
            </div>
            <div class="user-info">
                <?php echo htmlspecialchars($username); ?>님으로 로그인됨
                <span class="admin-badge">ADMIN</span>
                <div style="font-size: 11px; margin-top: 4px; color: #666;">
                    로그인: <?php echo $loginTime; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>관리자 대시보드</h2>
            <p>HCAMP 2025 보안 교육 플랫폼의 관리자 페이지입니다.<br>
            시스템 관리 및 사용자 관리 기능에 접근할 수 있습니다.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-title">시스템 현황</div>
                <div class="card-content">
                    서버 상태 및 시스템 리소스 모니터링
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">가동률</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">127</div>
                            <div class="stat-label">활성 사용자</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">4.2GB</div>
                            <div class="stat-label">메모리 사용</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-title">사용자 관리</div>
                <div class="card-content">
                    등록된 사용자 계정 및 권한 관리
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number">1,247</div>
                            <div class="stat-label">총 사용자</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">5</div>
                            <div class="stat-label">관리자</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">23</div>
                            <div class="stat-label">신규 가입</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-title">보안 로그</div>
                <div class="card-content">
                    보안 이벤트 및 로그 모니터링
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number">12</div>
                            <div class="stat-label">경고</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">심각</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">1,534</div>
                            <div class="stat-label">총 로그</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-title">교육 진행률</div>
                <div class="card-content">
                    보안 교육 과정 진행 현황
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number">78%</div>
                            <div class="stat-label">완료율</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">156</div>
                            <div class="stat-label">진행중</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24</div>
                            <div class="stat-label">새 과정</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="/logout.php" class="logout-btn">로그아웃</a>
        </div>
    </div>
</body>
</html>
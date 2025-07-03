<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Welcome to SecureConnect</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #1a237e, #0d47a1);
      color: #fff;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 2rem;
    }

h1 {
  font-weight: 600;
  font-size: 3rem;
  margin-bottom: 1rem;
  line-height: 1.2;
}


    p.intro {
      font-weight: 400;
      font-size: 1.25rem;
      margin-bottom: 2rem;
      max-width: 700px;
      animation: fadeInUp 1.5s ease-out;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      max-width: 1000px;
      width: 100%;
      margin-bottom: 3rem;
      animation: fadeInUp 2s ease-out;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 15px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      text-align: left;
      backdrop-filter: blur(10px);
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .feature-card i {
      color: #ffca28;
      margin-right: 10px;
      font-size: 1.2rem;
    }

    .btn {
      background-color: #ffca28;
      color: #1a237e;
      font-weight: 600;
      padding: 1rem 2rem;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      text-decoration: none;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      animation: fadeInUp 2.5s ease-out;
      margin-bottom: 3rem;
    }

    .btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }

    footer {
      font-size: 0.9rem;
      color: #bbb;
      padding: 1rem 0;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      width: 100%;
      text-align: center;
      animation: fadeIn 3s ease-out;
    }

    #loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #1a237e;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      flex-direction: column;
      color: #ffca28;
      font-weight: 600;
      font-size: 1.5rem;
    }

    .spinner {
      border: 6px solid #f3f3f3;
      border-top: 6px solid #ffca28;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
      margin-bottom: 1rem;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes fadeInUp {
      0% { opacity: 0; transform: translateY(40px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @media (max-width: 600px) {
      h1 {
        font-size: 2.25rem;
      }
      p.intro {
        font-size: 1rem;
      }
      .btn {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <div id="loader">
    <div class="spinner"></div>
    Loading SecureConnect...
  </div>

  <div id="content" style="display:none;">
    <h1>
  Welcome to<br />
  <span style="color: #fff;">SecureConnect</span> <span style="color: #ffca28;">Kenya</span>
</h1>
    <p class="intro">
      Your trusted security management system for organizations, guards, and administrators. Manage incidents, guard assignments, analytics, and more with ease.
    </p>

    <div class="features-grid">
      <div class="feature-card"><i class="fas fa-tasks"></i> Automates duty management to replace manual scheduling.</div>
      <div class="feature-card"><i class="fas fa-comments"></i> Enhances real-time communication between firms and clients.</div>
      <div class="feature-card"><i class="fas fa-user-shield"></i> Organizations can view, request, and adjust guard deployment.</div>
      <div class="feature-card"><i class="fas fa-map-marker-alt"></i> Improves visibility and accountability via real-time tracking.</div>
      <div class="feature-card"><i class="fas fa-chart-line"></i> Highlights security pain points using analytics and logs.</div>
    </div>

    <a href="index.php" class="btn">Get Started</a>
    <footer>
      &copy; 2024 SecureConnect. All rights reserved.
    </footer>
  </div>

  <script>
    window.addEventListener('load', function () {
      const loader = document.getElementById('loader');
      const content = document.getElementById('content');
      setTimeout(() => {
        loader.style.display = 'none';
        content.style.display = 'block';
      }, 2000); // Optimized for user experience
    });
  </script>
</body>
</html>

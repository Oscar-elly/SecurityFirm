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
      background: #fff;
      color: #333;
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

    .tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
      border-bottom: 2px solid #ffca28;
      background-color: #1a237e;
      width: 100%;
      padding: 0 2rem;
      box-sizing: border-box;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
    }

    .tab {
      padding: 1rem 2rem;
      cursor: pointer;
      font-weight: 600;
      color: #ffca28;
      border-bottom: 4px solid transparent;
      transition: border-color 0.3s ease;
    }

    .tab.active {
      border-color: #ffca28;
      color: #fff;
      background-color: #0d47a1;
      border-radius: 4px 4px 0 0;
    }

    .tab-content {
      max-width: 900px;
      width: 100%;
      text-align: left;
      animation: fadeInUp 1s ease-out;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 15px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      text-align: left;
      backdrop-filter: blur(10px);
      margin-bottom: 1rem;
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
      margin-top: 1rem;
      display: inline-block;
    }

    .btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }

    footer {
      font-size: 0.9rem;
      color: #fff;
      background-color: #1a237e;
      padding: 1rem 0;
      width: 100%;
      text-align: center;
      animation: fadeIn 3s ease-out;
      margin-top: auto;
      position: fixed;
      bottom: 0;
      left: 0;
      z-index: 1000;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      max-width: 400px;
    }

    input, textarea {
      padding: 0.75rem;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    input[type="submit"] {
      background-color: #ffca28;
      color: #1a237e;
      font-weight: 600;
      cursor: pointer;
      border: none;
      border-radius: 30px;
      padding: 1rem;
      transition: background-color 0.3s ease;
    }

    input[type="submit"]:hover {
      background-color: #f9a825;
    }

    /* Logo animation */
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
      font-size: 2rem;
      animation: fadeIn 1s ease-out;
    }

    #loader .logo {
      font-size: 3rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
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
        allign: center;
      }
      .tabs {
        flex-direction: column;
        gap: 0.5rem;
      }
      .tab {
        padding: 0.75rem 1rem;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <div id="loader">
    <div class="logo">SecureConnectKenya</div>
  </div>

  <div id="content" style="display:none; padding-top: 70px;">
    <div class="tabs">
      <div class="tab active" data-tab="home">Home</div>
      <div class="tab" data-tab="about">About Us</div>
      <div class="tab" data-tab="contact">Contact Us</div>
      <div class="tab" data-tab="login">Log In</div>
    </div>

    <div class="tab-content" id="home">
      <h1>
        Welcome to<br />
        <span style="color: green;">SecureConnect</span> <span style="color: #ffca28;">Kenya</span>
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
    </div>



    <div class="tab-content" id="about" style="display:none;">
      <h2>About Us</h2>
      <p>
        SecureConnect Kenya is a comprehensive security management system designed to streamline operations for organizations, guards, and administrators. Our platform enhances communication, scheduling, and analytics to improve security outcomes.
      </p>
    </div>

    <div class="tab-content" id="contact" style="display:none;">
      <h2>Contact Us</h2>
      <form id="contactForm" action="contact_submit.php" method="POST">
        <input type="text" name="name" placeholder="Your Name" required />
        <input type="email" name="email" placeholder="Your Email" required />
        <textarea name="message" placeholder="Your Message" required></textarea>
        <input type="submit" value="Send Message" />
      </form>
    </div>

    <div class="tab-content" id="login" style="display:none;">
          <!-- How It Works Section -->
    <div style="margin-top: 3rem;">
      <h2 style="text-align: center; color: #1a237e; margin-bottom: 1rem;">How It Works</h2>
      <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; max-width: 900px; margin: 0 auto;">
        <div style="background: #1a237e; color: white; padding: 1.5rem; border-radius: 10px; flex: 1 1 200px; min-width: 200px; text-align: center;">
          <div style="background: #ffca28; color: #1a237e; width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-weight: 700;">1</div>
          <p>Customers make reservations easily online or via phone.</p>
        </div>
        <div style="background: #1a237e; color: white; padding: 1.5rem; border-radius: 10px; flex: 1 1 200px; min-width: 200px; text-align: center;">
          <div style="background: #ffca28; color: #1a237e; width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-weight: 700;">2</div>
          <p>System sends automated confirmations and reminders.</p>
        </div>
        <div style="background: #1a237e; color: white; padding: 1.5rem; border-radius: 10px; flex: 1 1 200px; min-width: 200px; text-align: center;">
          <div style="background: #ffca28; color: #1a237e; width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-weight: 700;">3</div>
          <p>Staff manages tables using the optimized dashboard.</p>
        </div>
        <div style="background: #1a237e; color: white; padding: 1.5rem; border-radius: 10px; flex: 1 1 200px; min-width: 200px; text-align: center;">
          <div style="background: #ffca28; color: #1a237e; width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-weight: 700;">4</div>
          <p>Managers access analytics and adjust operations dynamically.</p>
        </div>
      </div>
    </div>
      <h2>Log In</h2>
      <a href="index.php" class="btn">Go to Login Page</a>
    </div>

    <footer>
      &copy; 2024 SecureConnect. All rights reserved.
    </footer>
  </div>

  <script>
    // Tab switching logic
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const target = tab.getAttribute('data-tab');
        tabContents.forEach(content => {
          if (content.id === target) {
            content.style.display = 'block';
          } else {
            content.style.display = 'none';
          }
        });
      });
    });

    // Loader animation and content display
    window.addEventListener('load', function () {
      const loader = document.getElementById('loader');
      const content = document.getElementById('content');
      setTimeout(() => {
        loader.style.display = 'none';
        content.style.display = 'block';
      }, 2000); // Duration of logo animation
    });
  </script>
</body>
</html>

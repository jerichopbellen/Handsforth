<?php
session_start();
include("includes/config.php");
include("includes/header.php");
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&family=Merriweather:wght@400;700&display=swap');

  :root {
    --hf-blue: #1f4f7a;
    --hf-blue-deep: #153a5b;
    --hf-gold: #ffd447;
    --hf-cream: #fffaf0;
    --hf-slate: #2f3640;
  }

  .landing-hero {
    min-height: 72vh;
    background:
      linear-gradient(115deg, rgba(21, 58, 91, 0.9), rgba(31, 79, 122, 0.72)),
      url('http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/assets/welcome-bg.jpg') center/cover no-repeat;
    position: relative;
    overflow: hidden;
  }

  .landing-hero::before,
  .landing-hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    filter: blur(2px);
    opacity: 0.35;
    animation: pulseShift 8s ease-in-out infinite;
  }

  .landing-hero::before {
    width: 320px;
    height: 320px;
    top: -80px;
    right: -80px;
    background: radial-gradient(circle, var(--hf-gold), transparent 70%);
  }

  .landing-hero::after {
    width: 260px;
    height: 260px;
    left: -60px;
    bottom: -60px;
    background: radial-gradient(circle, #8ec5fc, transparent 70%);
  }

  .hero-inner {
    position: relative;
    z-index: 2;
  }

  .hero-title {
    font-family: "Montserrat", sans-serif;
    font-weight: 800;
    letter-spacing: 0.4px;
  }

  .hero-copy {
    font-family: "Merriweather", serif;
    max-width: 62ch;
  }

  .chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 212, 71, 0.2);
    border: 1px solid rgba(255, 212, 71, 0.65);
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .glass-box {
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.35);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    color: #fff;
  }

  .explain-card {
    border: 0;
    border-radius: 14px;
    padding: 18px;
    text-align: left;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    cursor: pointer;
  }

  .explain-card:hover,
  .explain-card.active {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px rgba(21, 58, 91, 0.18);
    border-left: 5px solid var(--hf-gold);
  }

  .explain-card h5 {
    margin-bottom: 6px;
    color: var(--hf-blue-deep);
    font-family: "Montserrat", sans-serif;
    font-weight: 700;
  }

  .detail-panel {
    border-radius: 16px;
    background: linear-gradient(145deg, var(--hf-cream), #ffffff);
    border: 1px solid #e4e9ef;
  }

  .flow-box {
    border-radius: 16px;
    border: 1px solid #dde6ef;
    background: #fff;
  }

  .flow-step {
    border: 1px solid #c5d7ea;
    border-radius: 999px;
    padding: 7px 14px;
    background: #f7fbff;
    color: var(--hf-blue-deep);
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .flow-step.active,
  .flow-step:hover {
    background: var(--hf-blue);
    color: #fff;
    border-color: var(--hf-blue);
  }

  .fade-rise {
    animation: fadeRise 0.55s ease;
  }

  @keyframes fadeRise {
    from {
      opacity: 0;
      transform: translateY(12px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes pulseShift {
    0%, 100% {
      transform: translate(0, 0);
    }
    50% {
      transform: translate(-10px, 8px);
    }
  }

  @media (max-width: 768px) {
    .landing-hero {
      min-height: auto;
      padding: 2.5rem 0;
    }

    .hero-title {
      font-size: 1.9rem;
    }
  }
</style>

<main>
  <section class="landing-hero d-flex align-items-center">
    <div class="container hero-inner py-5">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7 text-white fade-rise">
          <span class="chip mb-3"><i class="bi bi-lightning-charge-fill"></i> Interactive Handsforth Overview</span>
          <h1 class="display-5 hero-title mb-3">Handsforth System Explanation</h1>
          <p class="lead hero-copy mb-4">
            This landing page gives a clear explanation of who developed Handsforth, the purpose of the platform,
            why beneficiaries are selected, and the core community problems Handsforth is designed to address.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/login.php" class="btn btn-warning btn-lg rounded-pill px-4 fw-semibold">
              <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
            </a>
            <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/index.php" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-semibold">
              <i class="bi bi-house-door me-2"></i>Back to Home
            </a>
          </div>
        </div>

        <div class="col-lg-5 fade-rise">
          <div class="glass-box p-4">
            <h4 class="fw-bold mb-3">Beneficiary Profile Preview</h4>
            <p class="mb-2">Pick a profile to see how Handsforth justifies beneficiary selection.</p>
            <select class="form-select" id="roleSelect" aria-label="Beneficiary profile selector">
              <option value="low_income" selected>Low-Income Household</option>
              <option value="student">Student Support Case</option>
              <option value="disaster">Disaster-Affected Family</option>
            </select>
            <div id="rolePreview" class="mt-3 p-3 rounded" style="background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.35);">
              Prioritized when household income, dependents, and urgent needs show high vulnerability during assessment.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="container py-5">
    <div class="text-center mb-4">
      <h2 class="fw-bold" style="font-family: 'Montserrat', sans-serif; color: var(--hf-blue-deep);">Clear Handsforth Explanation</h2>
      <p class="text-muted mb-0">Click each topic to display full details.</p>
    </div>

    <div class="row g-3" id="explanationCards">
      <div class="col-md-6 col-lg-3">
        <button class="explain-card active" data-key="developer" type="button">
          <h5><i class="bi bi-code-slash me-1"></i>Developer</h5>
          <p class="mb-0 text-muted">Who developed Handsforth.</p>
        </button>
      </div>
      <div class="col-md-6 col-lg-3">
        <button class="explain-card" data-key="purpose" type="button">
          <h5><i class="bi bi-bullseye me-1"></i>Purpose</h5>
          <p class="mb-0 text-muted">Why Handsforth was created.</p>
        </button>
      </div>
      <div class="col-md-6 col-lg-3">
        <button class="explain-card" data-key="reason" type="button">
          <h5><i class="bi bi-check2-circle me-1"></i>Reason for Selection</h5>
          <p class="mb-0 text-muted">Why beneficiaries are selected.</p>
        </button>
      </div>
      <div class="col-md-6 col-lg-3">
        <button class="explain-card" data-key="problem" type="button">
          <h5><i class="bi bi-shield-lock me-1"></i>Problem Addressed</h5>
          <p class="mb-0 text-muted">What Handsforth solves.</p>
        </button>
      </div>
    </div>

    <div class="detail-panel p-4 mt-4 fade-rise" id="detailPanel">
      <h4 id="detailTitle" class="mb-2" style="color: var(--hf-blue-deep); font-family: 'Montserrat', sans-serif;">Developer</h4>
      <p id="detailBody" class="mb-0 text-dark">
        Handsforth was developed by the Handsforth Development Team to support community service operations with one integrated platform.
      </p>
    </div>
  </section>

  <section class="container pb-5">
    <div class="flow-box p-4 shadow-sm">
      <h3 class="fw-bold mb-3" style="font-family: 'Montserrat', sans-serif; color: var(--hf-blue-deep);">Interactive Beneficiary Selection Flow</h3>
      <p class="text-muted">Select a step to preview how beneficiaries are selected fairly.</p>
      <div class="d-flex flex-wrap gap-2 mb-3" id="flowSteps">
        <button class="flow-step active" data-step="1" type="button">1. Intake Request</button>
        <button class="flow-step" data-step="2" type="button">2. Assess Need</button>
        <button class="flow-step" data-step="3" type="button">3. Validate Records</button>
        <button class="flow-step" data-step="4" type="button">4. Prioritize Support</button>
      </div>
      <div class="alert alert-primary mb-0" id="flowText">
        Community request is captured with basic family, health, and livelihood information.
      </div>
    </div>
  </section>
</main>

<script>
  const explanationDetails = {
    developer: {
      title: "Developer",
      body: "Handsforth was developed by the Handsforth Development Team to digitize community service work and make coordination between donors, volunteers, and administrators more effective."
    },
    purpose: {
      title: "Purpose",
      body: "The purpose of Handsforth is to manage beneficiaries, projects, volunteers, attendance, and donations in one system so assistance can be planned, tracked, and reported transparently."
    },
    reason: {
      title: "Reason for Selection",
      body: "Beneficiaries are selected based on need level, vulnerability, supporting records, and urgency so limited resources reach people who require immediate and meaningful support."
    },
    problem: {
      title: "Problem Addressed",
      body: "Handsforth addresses fragmented and manual outreach processes, delayed support decisions, and weak visibility in aid distribution by centralizing operations and records."
    }
  };

  const roleMessages = {
    low_income: "Prioritized when household income, dependents, and urgent needs show high vulnerability during assessment.",
    student: "Selected when financial limitations affect education access, attendance, or school continuity and support can prevent drop-out risk.",
    disaster: "Given urgent priority when displacement, property loss, or safety concerns require immediate relief and coordinated follow-up."
  };

  const flowMessages = {
    1: "Community request is captured with basic family, health, and livelihood information.",
    2: "Needs are assessed using impact, urgency, and available social support indicators.",
    3: "Team verifies submitted details and supporting documents for fairness and accountability.",
    4: "Cases are prioritized so assistance is delivered to the highest-need beneficiaries first."
  };

  const detailTitle = document.getElementById("detailTitle");
  const detailBody = document.getElementById("detailBody");
  const cards = document.querySelectorAll(".explain-card");
  const roleSelect = document.getElementById("roleSelect");
  const rolePreview = document.getElementById("rolePreview");
  const flowText = document.getElementById("flowText");
  const flowSteps = document.querySelectorAll(".flow-step");

  cards.forEach((card) => {
    card.addEventListener("click", () => {
      const selectedKey = card.dataset.key;
      const selectedDetail = explanationDetails[selectedKey];

      cards.forEach((btn) => btn.classList.remove("active"));
      card.classList.add("active");

      detailTitle.textContent = selectedDetail.title;
      detailBody.textContent = selectedDetail.body;

      const panel = document.getElementById("detailPanel");
      panel.classList.remove("fade-rise");
      void panel.offsetWidth;
      panel.classList.add("fade-rise");
    });
  });

  roleSelect.addEventListener("change", (event) => {
    rolePreview.textContent = roleMessages[event.target.value];
  });

  flowSteps.forEach((stepButton) => {
    stepButton.addEventListener("click", () => {
      flowSteps.forEach((btn) => btn.classList.remove("active"));
      stepButton.classList.add("active");
      flowText.textContent = flowMessages[stepButton.dataset.step];
    });
  });
</script>

<?php include("includes/footer.php"); ?>
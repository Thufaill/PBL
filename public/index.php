<?php
// public/index.php
// Landing Page Publik SkillBridge
require_once __DIR__ . '/../includes/auth/auth.php';

$page_title = 'SkillBridge · Discover Your Skills. Build Your Future.';
$role = 'public';
$path_prefix = '';
$body_class = 'landing';
$nav_active = 'home';

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <a class="lp-skip-link" href="#main">Skip to content</a>

    <?php require_once __DIR__ . '/../includes/layouts/public_header.php'; ?>

    <main id="main">
      <!-- ============ Hero ============ -->
      <section class="lp-hero" aria-labelledby="hero-title">
        <div class="lp-hero-orbit" aria-hidden="true">
          <img src="assets/landing/orbit.svg" alt="" width="1120" height="650" />
        </div>

        <div class="lp-hero-intro">
          <p class="lp-promise">
            <img src="assets/landing/sparkles.svg" alt="" width="16" height="16" />
            Your potential. Your next chapter.
          </p>
          <h1 class="lp-hero-title" id="hero-title">
            Discover Your Skills.
            <span>Build Your Future.</span>
          </h1>
          <p class="lp-hero-desc">
            Explore learning opportunities, develop in-demand skills, and connect your potential with
            meaningful career opportunities.
          </p>
        </div>

        <form class="lp-search" id="hero-search" role="search">
          <label class="lp-search-field">
            <span class="visually-hidden">Search skills, courses, or career paths</span>
            <img src="assets/landing/search.svg" alt="" width="21" height="21" aria-hidden="true" />
            <input
              type="search"
              id="hero-search-input"
              name="q"
              placeholder="Search skills, courses, or career paths"
              autocomplete="off"
            />
          </label>
          <button class="lp-btn lp-btn-accent" type="submit">Explore Opportunities</button>
        </form>

        <div class="lp-popular">
          <span class="lp-popular-label">Start with a skill:</span>
          <a class="lp-chip" href="mahasiswa/skills.php">
            <img src="assets/landing/status-dot.svg" alt="" width="6" height="6" aria-hidden="true" />
            User research
          </a>
          <a class="lp-chip" href="mahasiswa/skills.php">
            <img src="assets/landing/status-dot.svg" alt="" width="6" height="6" aria-hidden="true" />
            JavaScript
          </a>
          <a class="lp-chip" href="mahasiswa/skills.php">
            <img src="assets/landing/status-dot.svg" alt="" width="6" height="6" aria-hidden="true" />
            Communication
          </a>
        </div>

        <div class="lp-previews">
          <article class="lp-preview lp-preview--skills">
            <h3>Discover what you can build</h3>
            <div class="lp-growth-row">
              <span class="lp-growth-icon" aria-hidden="true">PS</span>
              <span class="lp-growth-text">
                <strong>Prototyping</strong>
                <span>Build a project</span>
              </span>
            </div>
            <p class="lp-preview-note">Make your strengths visible, one skill at a time.</p>
          </article>

          <article class="lp-preview lp-preview--portfolio">
            <div class="lp-preview-head">
              <div class="lp-preview-head-left">
                <span class="lp-icon-36">
                  <img src="assets/landing/globe-2.svg" alt="" width="17" height="17" aria-hidden="true" />
                </span>
                <span class="lp-badge-soft">Portfolio</span>
              </div>
            </div>
            <h3>Your work tells your story</h3>
            <p>Bring projects, reflections, and certificates into your portfolio.</p>
            <div class="lp-preview-foot">
              <span>Illustrative portfolio</span>
              <a class="lp-text-action" href="mahasiswa/evidence.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-preview lp-preview--career">
            <div class="lp-preview-head">
              <h3>Find your direction</h3>
              <span class="lp-icon-36 lp-icon-36--gray">
                <img
                  src="assets/landing/briefcase-business.svg"
                  alt=""
                  width="17"
                  height="17"
                  aria-hidden="true"
                />
              </span>
            </div>
            <strong>A clearer next step</strong>
            <p>Connect the skills you are building to a career target.</p>
          </article>
        </div>
      </section>

      <!-- ============ Community strip ============ -->
      <section class="lp-strip" aria-label="Our commitment">
        <strong>Built for people who want to keep growing.</strong>
        <p class="lp-strip-note">
          <img src="assets/landing/sprout.svg" alt="" width="20" height="20" aria-hidden="true" />
          Stay curious. Build evidence. Move forward.
        </p>
      </section>

      <!-- ============ How it works ============ -->
      <section class="lp-how" aria-labelledby="how-title">
        <div class="lp-section-head lp-section-head--center">
          <p class="lp-eyebrow">A little progress, a clearer direction</p>
          <h2 class="lp-h2" id="how-title">How SkillBridge Works</h2>
          <p class="lp-lead">Start where you are. Take the next step that feels right for you.</p>
        </div>

        <ol class="lp-steps" style="list-style: none; padding: 0; margin: 0">
          <li class="lp-step">
            <div class="lp-step-marker">
              <span class="lp-step-icon">
                <img src="assets/landing/user-round.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-step-num">01</span>
            </div>
            <h3>Create Your Profile</h3>
            <p>Start with your interests, strengths, and the direction you want to explore.</p>
          </li>

          <li class="lp-step">
            <div class="lp-step-marker">
              <span class="lp-step-icon">
                <img src="assets/landing/scan-search.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-step-num">02</span>
            </div>
            <h3>Discover Your Skills</h3>
            <p>Recognize what you already know and find the skills you want to build.</p>
          </li>

          <li class="lp-step">
            <div class="lp-step-marker">
              <span class="lp-step-icon">
                <img src="assets/landing/route.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-step-num">03</span>
            </div>
            <h3>Explore Learning Paths</h3>
            <p>Find learning opportunities that give your next step a little more focus.</p>
          </li>

          <li class="lp-step">
            <div class="lp-step-marker">
              <span class="lp-step-icon">
                <img src="assets/landing/flag.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-step-num">04</span>
            </div>
            <h3>Prepare for Your Next Opportunity</h3>
            <p>Collect evidence of your learning and connect it to your career goals.</p>
          </li>
        </ol>
      </section>

      <!-- ============ Learning paths ============ -->
      <section class="lp-paths" id="learning-paths" aria-labelledby="paths-title">
        <div class="lp-paths-head">
          <div class="lp-section-head">
            <p class="lp-eyebrow">Make room for what's next</p>
            <h2 class="lp-h2" id="paths-title">Explore Featured Learning Paths</h2>
            <p class="lp-lead">Find a starting point for the skills you want to develop.</p>
          </div>
          <a class="lp-text-action" href="mahasiswa/skills.php">
            View All
            <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
          </a>
        </div>

        <div class="lp-path-grid">
          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img
                  src="assets/landing/panels-top-left.svg"
                  alt=""
                  width="24"
                  height="24"
                  aria-hidden="true"
                />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>UI/UX Design Fundamentals</h3>
              <p>Shape thoughtful digital experiences, from first insight to a working prototype.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">User research</span>
              <span class="lp-chip">Wireframing</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img src="assets/landing/code-xml.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>Frontend Development</h3>
              <p>Bring ideas to life with accessible interfaces and responsive web experiences.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">JavaScript</span>
              <span class="lp-chip">React</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img
                  src="assets/landing/chart-no-axes-combined.svg"
                  alt=""
                  width="24"
                  height="24"
                  aria-hidden="true"
                />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>Data Analytics</h3>
              <p>Explore patterns in data and turn your findings into a story people can use.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">SQL</span>
              <span class="lp-chip">Python</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img src="assets/landing/megaphone.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>Digital Marketing</h3>
              <p>Connect with an audience through purposeful content and clear messaging.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">Content strategy</span>
              <span class="lp-chip">SEO</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img src="assets/landing/layers.svg" alt="" width="24" height="24" aria-hidden="true" />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>Product Management</h3>
              <p>Define a meaningful problem, prioritize ideas, and guide a product forward.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">Prioritization</span>
              <span class="lp-chip">Discovery</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-path-card">
            <div class="lp-path-identity">
              <span class="lp-path-icon">
                <img
                  src="assets/landing/messages-square.svg"
                  alt=""
                  width="24"
                  height="24"
                  aria-hidden="true"
                />
              </span>
              <span class="lp-path-kind">Learning Path</span>
            </div>
            <div class="lp-path-copy">
              <h3>Business Communication</h3>
              <p>Build confidence in presenting ideas, writing clearly, and collaborating.</p>
            </div>
            <div class="lp-path-skills">
              <span class="lp-chip">Presentation</span>
              <span class="lp-chip">Collaboration</span>
            </div>
            <div class="lp-path-foot">
              <a class="lp-text-action" href="mahasiswa/skills.php">
                Explore
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>
        </div>

        <p class="lp-footnote">
          Illustrative learning paths · Concepts for exploration, not a verified course catalog.
        </p>
      </section>

      <!-- ============ Skill categories ============ -->
      <section class="lp-categories" id="categories" aria-labelledby="categories-title">
        <div class="lp-section-head lp-section-head--center">
          <p class="lp-eyebrow">Follow your curiosity</p>
          <h2 class="lp-h2" id="categories-title">Find Your Path by Skill Category</h2>
          <p class="lp-lead">Different interests. More possibilities. One place to begin.</p>
        </div>

        <div class="lp-cat-grid">
          <a class="lp-cat-tile is-featured" href="mahasiswa/skills.php">
            <img src="assets/landing/cat-pen-tool.svg" alt="" width="22" height="22" aria-hidden="true" />
            <span>Design</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img src="assets/landing/cat-code-xml.svg" alt="" width="22" height="22" aria-hidden="true" />
            <span>Technology</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img
              src="assets/landing/cat-chart-no-axes-combined.svg"
              alt=""
              width="22"
              height="22"
              aria-hidden="true"
            />
            <span>Data &amp; Analytics</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img src="assets/landing/cat-megaphone.svg" alt="" width="22" height="22" aria-hidden="true" />
            <span>Marketing</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img
              src="assets/landing/cat-briefcase-business.svg"
              alt=""
              width="22"
              height="22"
              aria-hidden="true"
            />
            <span>Business</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img src="assets/landing/cat-settings-2.svg" alt="" width="22" height="22" aria-hidden="true" />
            <span>Engineering</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img
              src="assets/landing/cat-messages-square.svg"
              alt=""
              width="22"
              height="22"
              aria-hidden="true"
            />
            <span>Communication</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
          <a class="lp-cat-tile" href="mahasiswa/skills.php">
            <img src="assets/landing/cat-compass.svg" alt="" width="22" height="22" aria-hidden="true" />
            <span>Leadership</span>
            <img src="assets/landing/arrow-up-right.svg" alt="" width="16" height="16" aria-hidden="true" />
          </a>
        </div>
      </section>

      <!-- ============ Growing together ============ -->
      <section class="lp-growing" aria-labelledby="growing-title">
        <div class="lp-section-head">
          <p class="lp-eyebrow">Growth looks different for everyone</p>
          <h2 class="lp-h2" id="growing-title">Growing Together with SkillBridge</h2>
          <p class="lp-lead">A shared commitment to learning. A journey that is uniquely yours.</p>
        </div>

        <div class="lp-benefits">
          <article class="lp-benefit">
            <div class="lp-benefit-art">
              <img
                class="lp-halo"
                src="assets/landing/illustration-halo.svg"
                alt=""
                width="116"
                height="116"
                aria-hidden="true"
              />
              <img
                class="lp-art"
                src="assets/landing/strength-bloom.svg"
                alt=""
                width="92"
                height="92"
                aria-hidden="true"
              />
            </div>
            <p class="lp-benefit-tag">Self-discovery</p>
            <h3>Recognize your strengths</h3>
            <p>Give the skills you already have a name. Notice where your interests and abilities come together.</p>
          </article>

          <article class="lp-benefit">
            <div class="lp-benefit-art">
              <img
                class="lp-halo"
                src="assets/landing/illustration-halo.svg"
                alt=""
                width="116"
                height="116"
                aria-hidden="true"
              />
              <img
                class="lp-art"
                src="assets/landing/evidence-stack.svg"
                alt=""
                width="100"
                height="100"
                aria-hidden="true"
              />
            </div>
            <p class="lp-benefit-tag">Meaningful evidence</p>
            <h3>Show what you can do</h3>
            <p>
              Connect your skills to projects, certificates, and reflections. Let your evidence tell a more
              complete story.
            </p>
          </article>

          <article class="lp-benefit">
            <div class="lp-benefit-art">
              <img
                class="lp-halo"
                src="assets/landing/illustration-halo.svg"
                alt=""
                width="116"
                height="116"
                aria-hidden="true"
              />
              <img
                class="lp-art"
                src="assets/landing/next-step-illustration.svg"
                alt=""
                width="100"
                height="100"
                aria-hidden="true"
              />
            </div>
            <p class="lp-benefit-tag">Purposeful progress</p>
            <h3>Choose a clearer next step</h3>
            <p>Bring your learning and career direction into focus. Make progress with a next step you can act on.</p>
          </article>
        </div>
      </section>

      <!-- ============ Skills to possibilities ============ -->
      <section class="lp-possibilities" aria-labelledby="possibilities-title">
        <div class="lp-possibilities-copy">
          <div class="lp-section-head">
            <p class="lp-eyebrow">Bring it all together</p>
            <h2 class="lp-h2" id="possibilities-title">Connect Your Skills to New Possibilities</h2>
            <p class="lp-lead">
              Your skills are more than a list. Connect what you know, what you learn, and what you create to a
              direction that matters to you.
            </p>
          </div>

          <ul class="lp-checklist" style="list-style: none; padding: 0; margin: 0">
            <li class="lp-check">
              <img src="assets/landing/circle-check.svg" alt="" width="20" height="20" aria-hidden="true" />
              Discover relevant skills and learning opportunities.
            </li>
            <li class="lp-check">
              <img src="assets/landing/circle-check.svg" alt="" width="20" height="20" aria-hidden="true" />
              Build a clearer path toward your career goals.
            </li>
            <li class="lp-check">
              <img src="assets/landing/circle-check.svg" alt="" width="20" height="20" aria-hidden="true" />
              Connect your learning progress with future opportunities.
            </li>
          </ul>
        </div>

        <div class="lp-map" role="img" aria-label="Skills connect to learning, evidence, and a career target.">
          <img
            class="lp-map-orbit lp-map-orbit--inner"
            src="assets/landing/connection-orbit.svg"
            alt=""
            width="259"
            height="259"
            aria-hidden="true"
          />
          <img
            class="lp-map-orbit lp-map-orbit--outer"
            src="assets/landing/connection-orbit-1.svg"
            alt=""
            width="367"
            height="367"
            aria-hidden="true"
          />
          <img
            class="lp-map-connections"
            src="assets/landing/connections.svg"
            alt=""
            width="293"
            height="330"
            aria-hidden="true"
          />

          <div class="lp-map-core">
            <strong>S</strong>
            <span>YOUR POTENTIAL</span>
          </div>

          <div class="lp-map-node lp-map-node--skills">
            <img src="assets/landing/sparkles-dark.svg" alt="" width="20" height="20" aria-hidden="true" />
            <strong>Skills</strong>
            <span>What you can do</span>
          </div>

          <div class="lp-map-node lp-map-node--learning">
            <img src="assets/landing/book-open.svg" alt="" width="20" height="20" aria-hidden="true" />
            <strong>Learning</strong>
            <span>What comes next</span>
          </div>

          <div class="lp-map-node lp-map-node--evidence">
            <img src="assets/landing/folder-check.svg" alt="" width="20" height="20" aria-hidden="true" />
            <strong>Evidence</strong>
            <span>Your work, visible</span>
          </div>

          <div class="lp-map-node lp-map-node--career">
            <img src="assets/landing/target.svg" alt="" width="20" height="20" aria-hidden="true" />
            <strong>Career target</strong>
            <span>Your direction</span>
          </div>
        </div>
      </section>

      <!-- ============ Next step invitations ============ -->
      <section class="lp-invitations" aria-label="Get started with SkillBridge">
        <article class="lp-invite lp-invite--pink">
          <img src="assets/landing/book-open-accent.svg" alt="" width="32" height="32" aria-hidden="true" />
          <h3>Ready to Grow Your Skills?</h3>
          <p>Discover learning opportunities and take the next step toward your goals.</p>
          <div class="lp-invite-action">
            <a class="lp-btn lp-btn-accent" href="mahasiswa/skills.php">Start Learning</a>
          </div>
        </article>

        <article class="lp-invite lp-invite--dark">
          <img src="assets/landing/folder-check-light.svg" alt="" width="32" height="32" aria-hidden="true" />
          <h3>Give Your Skills a Story</h3>
          <p>
            Bring your projects and learning evidence together in a portfolio that shows what you can do.
          </p>
          <div class="lp-invite-action">
            <a class="lp-btn lp-btn-dark" href="mahasiswa/evidence.php">Build Your Portfolio</a>
          </div>
        </article>
      </section>

      <!-- ============ Insights ============ -->
      <section class="lp-insights" id="insights" aria-labelledby="insights-title">
        <div class="lp-section-head lp-section-head--center">
          <p class="lp-eyebrow">A little insight goes a long way</p>
          <h2 class="lp-h2" id="insights-title">Insights for Your Next Career Step</h2>
          <p class="lp-lead">Ideas to help you build skills, make your work visible, and find your direction.</p>
        </div>

        <div class="lp-resource-grid">
          <article class="lp-resource">
            <div class="lp-resource-thumb lp-resource-thumb--pink">
              <img
                class="lp-backdrop"
                src="assets/landing/backdrop-orbit-accent.svg"
                alt=""
                width="240"
                height="240"
                aria-hidden="true"
              />
              <div class="lp-resource-art lp-art-skills">
                <span class="lp-art-block lp-art-block--1"></span>
                <span class="lp-art-block lp-art-block--2"></span>
                <span class="lp-art-block lp-art-block--3"></span>
                <img
                  class="lp-art-symbol lp-art-symbol--1"
                  src="assets/landing/messages-square-30.svg"
                  alt=""
                  width="30"
                  height="30"
                  aria-hidden="true"
                />
                <img
                  class="lp-art-symbol lp-art-symbol--2"
                  src="assets/landing/lightbulb-30.svg"
                  alt=""
                  width="30"
                  height="30"
                  aria-hidden="true"
                />
                <img
                  class="lp-art-symbol lp-art-symbol--3"
                  src="assets/landing/code-xml-30.svg"
                  alt=""
                  width="30"
                  height="30"
                  aria-hidden="true"
                />
                <img
                  class="lp-art-line"
                  src="assets/landing/growth-line-30.svg"
                  alt=""
                  width="192"
                  height="73"
                  aria-hidden="true"
                />
              </div>
            </div>
            <div class="lp-resource-details">
              <p class="lp-resource-tag">Skill development</p>
              <h3>Skills Employers Value</h3>
              <p>Explore how problem-solving, communication, and digital skills can support your next step.</p>
              <a class="lp-text-action" href="#insights" data-article="skills-employers-value">
                Read More
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-resource">
            <div class="lp-resource-thumb lp-resource-thumb--indigo">
              <img
                class="lp-backdrop"
                src="assets/landing/backdrop-orbit-1.svg"
                alt=""
                width="240"
                height="240"
                aria-hidden="true"
              />
              <div class="lp-resource-art lp-art-portfolio">
                <span class="lp-art-backing"></span>
                <span class="lp-art-page"></span>
                <span class="lp-art-cover"></span>
                <span class="lp-art-title"></span>
                <span class="lp-art-line lp-art-line--1"></span>
                <span class="lp-art-tile"></span>
                <img
                  class="lp-art-shape"
                  src="assets/landing/cover-shape.svg"
                  alt=""
                  width="40"
                  height="40"
                  aria-hidden="true"
                />
                <img
                  class="lp-art-symbol"
                  src="assets/landing/folder-check-accent.svg"
                  alt=""
                  width="30"
                  height="30"
                  aria-hidden="true"
                />
              </div>
            </div>
            <div class="lp-resource-details">
              <p class="lp-resource-tag">Portfolio &amp; evidence</p>
              <h3>Building a Career-Ready Portfolio</h3>
              <p>Turn your projects into clear examples of your thinking, contribution, and learning.</p>
              <a class="lp-text-action" href="#insights" data-article="career-ready-portfolio">
                Read More
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>

          <article class="lp-resource">
            <div class="lp-resource-thumb lp-resource-thumb--pink">
              <img
                class="lp-backdrop"
                src="assets/landing/backdrop-orbit.svg"
                alt=""
                width="240"
                height="240"
                aria-hidden="true"
              />
              <div class="lp-resource-art lp-art-learning">
                <img
                  src="assets/landing/resource-artwork.svg"
                  alt=""
                  width="250"
                  height="160"
                  aria-hidden="true"
                />
              </div>
            </div>
            <div class="lp-resource-details">
              <p class="lp-resource-tag">Learning &amp; discovery</p>
              <h3>Getting Started with Data Analytics</h3>
              <p>A first look at asking better questions, exploring data, and explaining what you find.</p>
              <a class="lp-text-action" href="#insights" data-article="getting-started-data-analytics">
                Read More
                <img src="assets/landing/external-link.svg" alt="" width="13" height="13" aria-hidden="true" />
              </a>
            </div>
          </article>
        </div>

        <p class="lp-footnote">
          Editorial previews · Illustrative resources, not a verified course catalog.
        </p>
      </section>
    </main>

    <?php require_once __DIR__ . '/../includes/layouts/public_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/landing.js')) ?>"></script>
  </body>
</html>
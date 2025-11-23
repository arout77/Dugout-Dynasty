<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* layouts/main.twig */
class __TwigTemplate_94eb8c688a1dbf4d7559d47ae3b9194c extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'full_width_content' => [$this, 'block_full_width_content'],
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<!DOCTYPE html>
<html lang=\"en\" class=\"antialiased\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>";
        // line 6
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "title", [], "any", false, false, false, 6), "html", null, true);
        yield "</title>
    <meta name=\"description\" content=\"";
        // line 7
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "description", [], "any", false, false, false, 7), "html_attr");
        yield "\">
    
    ";
        // line 10
        yield "    ";
        $context["current_url"] = (($context["app_url"] ?? null) . CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["app"] ?? null), "request", [], "any", false, false, false, 10), "uri", [], "any", false, false, false, 10));
        // line 11
        yield "    <link rel=\"canonical\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["current_url"] ?? null), "html", null, true);
        yield "\">

    <!-- Open Graph / Facebook -->
    <meta property=\"og:type\" content=\"website\">
    <meta property=\"og:url\" content=\"";
        // line 15
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["current_url"] ?? null), "html", null, true);
        yield "\">
    <meta property=\"og:title\" content=\"";
        // line 16
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "title", [], "any", false, false, false, 16), "html", null, true);
        yield "\">
    <meta property=\"og:description\" content=\"";
        // line 17
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "description", [], "any", false, false, false, 17), "html_attr");
        yield "\">
    <meta property=\"og:image\" content=\"";
        // line 18
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
        yield "/public/img/logo.png\">

    <!-- Twitter -->
    <meta property=\"twitter:card\" content=\"summary_large_image\">
    <meta property=\"twitter:url\" content=\"";
        // line 22
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["current_url"] ?? null), "html", null, true);
        yield "\">
    <meta property=\"twitter:title\" content=\"";
        // line 23
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "title", [], "any", false, false, false, 23), "html", null, true);
        yield "\">
    <meta property=\"twitter:description\" content=\"";
        // line 24
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["meta"] ?? null), "description", [], "any", false, false, false, 24), "html_attr");
        yield "\">
    <meta property=\"twitter:image\" content=\"";
        // line 25
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
        yield "/public/img/logo.png\">
    <link rel=\"icon\" href=\"favicon.ico\" type=\"image/x-icon\">
    
    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">
    <link href=\"";
        // line 29
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
        yield "/public/css/style.css\" rel=\"stylesheet\">
    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
    <link href=\"https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap\" rel=\"stylesheet\">
    <script>
        // This script runs before the rest of the page to prevent \"flashing\"
        // It sets the theme based on the user's saved preference or OS setting.
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- JSON-LD Schema Markup for SEO -->
    <script type=\"application/ld+json\">
    {
      \"@context\": \"https://schema.org\",
      \"@type\": \"SoftwareApplication\",
      \"name\": \"Rhapsody Framework\",
      \"description\": \"Rhapsody is a lightweight, modern PHP framework for building elegant and maintainable web applications. It provides a solid foundation with powerful features like a flexible router, a secure authentication system, a robust validation engine, and an efficient templating system powered by Twig.\",
      \"applicationCategory\": \"DeveloperApplication\",
      \"operatingSystem\": \"Web\",
      \"softwareVersion\": \"";
        // line 51
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["config"] ?? null), "app_version", [], "any", false, false, false, 51), "html", null, true);
        yield "\",
      \"url\": \"";
        // line 52
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_url"] ?? null), "html", null, true);
        yield "\",
      \"author\": {
        \"@type\": \"Person\",
        \"name\": \"Andrew Rout\"
      },
      \"programmingLanguage\": {
        \"@type\": \"ComputerLanguage\",
        \"name\": \"PHP\"
      },
      \"image\": \"";
        // line 61
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_url"] ?? null), "html", null, true);
        yield "/public/img/logo.png\",
      \"offers\": {
        \"@type\": \"Offer\",
        \"price\": \"0\",
        \"priceCurrency\": \"USD\"
      },
      \"featureList\": [
        \"Service Container & Dependency Injection\",
        \"Expressive Routing\",
        \"Middleware Pipeline & Authentication\",
        \"The Rhapsody Console (CLI)\",
        \"Database Migrations\",
        \"Robust Validation Engine\",
        \"Twig Templating\",
        \"Performance Caching (Routes & Application)\",
        \"Tailwind CSS Integration\"
      ]
    }
    </script>
</head>
<body class=\"bg-gray-200 dark:bg-gray-900 text-gray-800 dark:text-gray-200 font-sans transition-colors duration-700\">
    
    ";
        // line 83
        yield from $this->load("nav.twig", 83)->unwrap()->yield($context);
        // line 84
        yield "
    ";
        // line 85
        yield from $this->unwrap()->yieldBlock('full_width_content', $context, $blocks);
        // line 86
        yield "    
    <div class=\"container mx-auto p-4 sm:p-6 lg:p-8\">
        <main class=\"bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md\">
            ";
        // line 89
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["flash"] ?? null), "success", [], "any", false, false, false, 89)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 90
            yield "                <div class=\"bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6\" role=\"alert\">
                    <strong class=\"font-bold\">Success!</strong>
                    <span class=\"block sm:inline\">";
            // line 92
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["flash"] ?? null), "success", [], "any", false, false, false, 92), "html", null, true);
            yield "</span>
                </div>
            ";
        }
        // line 95
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["flash"] ?? null), "error", [], "any", false, false, false, 95)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 96
            yield "                <div class=\"bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6\" role=\"alert\">
                    <strong class=\"font-bold\">Error!</strong>
                    <span class=\"block sm:inline\">";
            // line 98
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["flash"] ?? null), "error", [], "any", false, false, false, 98), "html", null, true);
            yield "</span>
                </div>
            ";
        }
        // line 101
        yield "            ";
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 102
        yield "        </main>
    </div>

    <div id=\"update-modal\" class=\"absolute inset-0 z-50 hidden flex items-center justify-center p-4 bg-black bg-opacity-75\" style=\"background-color: #000000cf;\">
        <div id=\"update-modal-content\" class=\"bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col\">
            <div class=\"flex justify-between bg-pink-700 items-center p-4 border-b dark:border-gray-700\">
                <h3 class=\"text-2xl font-bold text-white\">Update Available</h3>
                <button id=\"close-update-modal\" class=\"text-gray-200 hover:text-gray-700 dark:hover:text-white text-3xl leading-none\">&times;</button>
            </div>
            <div class=\"p-6 overflow-y-auto\">
                <h4 id=\"update-version-tag\" class=\"text-xl font-bold mb-4\"></h4>
                <div class=\"prose dark:prose-invert max-w-none\">
                    <p>The following updates are available. It is recommended to run the update command from your terminal.</p>
                    <pre class=\"command-line\"><code class=\"language-bash\">php rhapsody app:update</code></pre>
                    <hr><br>
                    <h4>Release Notes:</h4>
                    <div id=\"update-release-notes\" class=\"text-sm p-4 bg-gray-200 dark:bg-gray-900 dark:text-gray-200 rounded-md\">
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
            <div class=\"flex justify-end items-center p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700\">
                <button id=\"close-update-modal-footer\" class=\"bg-pink-700  hover:bg-gray-400 text-white font-bold py-2 px-4 rounded\">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- MOBILE MENU LOGIC ---
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
            // --- THEME CYCLER LOGIC ---
            const themeBtn = document.getElementById('theme-btn');
            const themes = ['dark', 'light', 'system'];
            const icons = {
                dark: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\" style=\"color: yellow;\"><path d=\"M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z\" /></svg>`,
                light: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\" style=\"color: orange;\"><path d=\"M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z\"/></svg>`,
                system: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\"><path fill-rule=\"evenodd\" d=\"M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z\" clip-rule=\"evenodd\" /></svg>`
            };
            const titles = {
                dark: 'Switch to Light Mode',
                light: 'Switch to System Preference',
                system: 'Switch to Dark Mode'
            };

            let currentThemeIndex = 0;

            const applyTheme = (theme) => {
                if (theme === 'system') {
                    localStorage.removeItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    if (systemTheme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } else {
                    localStorage.setItem('theme', theme);
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
                updateButtonUI(theme);
            };

            const updateButtonUI = (theme) => {
                themeBtn.innerHTML = icons[theme];
                themeBtn.title = titles[theme];
                currentThemeIndex = themes.indexOf(theme);
            };

            themeBtn.addEventListener('click', () => {
                currentThemeIndex = (currentThemeIndex + 1) % themes.length;
                const nextTheme = themes[currentThemeIndex];
                applyTheme(nextTheme);
            });

            // Set initial state on page load
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme && themes.includes(savedTheme)) {
                applyTheme(savedTheme);
            } else {
                applyTheme('system'); // Default to system
            }

            // --- UPDATE MODAL LOGIC ---
            const updateLink = document.getElementById('update-notification-link');
            const updateModal = document.getElementById('update-modal');
            
            if (updateLink && updateModal) {
                const closeButtons = document.querySelectorAll('#close-update-modal, #close-update-modal-footer');
                const versionTagEl = document.getElementById('update-version-tag');
                const releaseNotesEl = document.getElementById('update-release-notes');

                updateLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const newVersion = updateLink.dataset.version;
                    versionTagEl.textContent = `New Version: \${newVersion}`;
                    releaseNotesEl.innerHTML = '<p>Loading release notes...</p>';
                    updateModal.classList.remove('hidden');

                    fetch('https://api.github.com/repos/arout77/rhapsody/releases/latest')
                        .then(response => response.json())
                        .then(data => {
                            if (data.body) {
                                releaseNotesEl.innerHTML = `<pre class=\"whitespace-pre-wrap text-sm\">\${data.body}</pre>`;
                            } else {
                                releaseNotesEl.textContent = 'No release notes were provided for this version.';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching release notes:', error);
                            releaseNotesEl.textContent = 'Could not load release notes.';
                        });
                });

                const closeModal = () => {
                    updateModal.classList.add('hidden');
                };

                closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
                
                updateModal.addEventListener('click', (e) => {
                    if (e.target === updateModal) {
                        closeModal();
                    }
                });
            }
        });
    </script>
</body>
</html>";
        yield from [];
    }

    // line 85
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_full_width_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    // line 101
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "layouts/main.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  370 => 101,  360 => 85,  215 => 102,  212 => 101,  206 => 98,  202 => 96,  199 => 95,  193 => 92,  189 => 90,  187 => 89,  182 => 86,  180 => 85,  177 => 84,  175 => 83,  150 => 61,  138 => 52,  134 => 51,  109 => 29,  102 => 25,  98 => 24,  94 => 23,  90 => 22,  83 => 18,  79 => 17,  75 => 16,  71 => 15,  63 => 11,  60 => 10,  55 => 7,  51 => 6,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<!DOCTYPE html>
<html lang=\"en\" class=\"antialiased\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{{ meta.title }}</title>
    <meta name=\"description\" content=\"{{ meta.description|e('html_attr') }}\">
    
    {# Assumes the full URL is needed for canonical and OG tags #}
    {% set current_url = app_url ~ app.request.uri %}
    <link rel=\"canonical\" href=\"{{ current_url }}\">

    <!-- Open Graph / Facebook -->
    <meta property=\"og:type\" content=\"website\">
    <meta property=\"og:url\" content=\"{{ current_url }}\">
    <meta property=\"og:title\" content=\"{{ meta.title }}\">
    <meta property=\"og:description\" content=\"{{ meta.description|e('html_attr') }}\">
    <meta property=\"og:image\" content=\"{{ base_url }}/public/img/logo.png\">

    <!-- Twitter -->
    <meta property=\"twitter:card\" content=\"summary_large_image\">
    <meta property=\"twitter:url\" content=\"{{ current_url }}\">
    <meta property=\"twitter:title\" content=\"{{ meta.title }}\">
    <meta property=\"twitter:description\" content=\"{{ meta.description|e('html_attr') }}\">
    <meta property=\"twitter:image\" content=\"{{ base_url }}/public/img/logo.png\">
    <link rel=\"icon\" href=\"favicon.ico\" type=\"image/x-icon\">
    
    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">
    <link href=\"{{ base_url }}/public/css/style.css\" rel=\"stylesheet\">
    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
    <link href=\"https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap\" rel=\"stylesheet\">
    <script>
        // This script runs before the rest of the page to prevent \"flashing\"
        // It sets the theme based on the user's saved preference or OS setting.
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- JSON-LD Schema Markup for SEO -->
    <script type=\"application/ld+json\">
    {
      \"@context\": \"https://schema.org\",
      \"@type\": \"SoftwareApplication\",
      \"name\": \"Rhapsody Framework\",
      \"description\": \"Rhapsody is a lightweight, modern PHP framework for building elegant and maintainable web applications. It provides a solid foundation with powerful features like a flexible router, a secure authentication system, a robust validation engine, and an efficient templating system powered by Twig.\",
      \"applicationCategory\": \"DeveloperApplication\",
      \"operatingSystem\": \"Web\",
      \"softwareVersion\": \"{{ config.app_version }}\",
      \"url\": \"{{ app_url }}\",
      \"author\": {
        \"@type\": \"Person\",
        \"name\": \"Andrew Rout\"
      },
      \"programmingLanguage\": {
        \"@type\": \"ComputerLanguage\",
        \"name\": \"PHP\"
      },
      \"image\": \"{{ app_url }}/public/img/logo.png\",
      \"offers\": {
        \"@type\": \"Offer\",
        \"price\": \"0\",
        \"priceCurrency\": \"USD\"
      },
      \"featureList\": [
        \"Service Container & Dependency Injection\",
        \"Expressive Routing\",
        \"Middleware Pipeline & Authentication\",
        \"The Rhapsody Console (CLI)\",
        \"Database Migrations\",
        \"Robust Validation Engine\",
        \"Twig Templating\",
        \"Performance Caching (Routes & Application)\",
        \"Tailwind CSS Integration\"
      ]
    }
    </script>
</head>
<body class=\"bg-gray-200 dark:bg-gray-900 text-gray-800 dark:text-gray-200 font-sans transition-colors duration-700\">
    
    {% include('nav.twig') %}

    {% block full_width_content %}{% endblock %}
    
    <div class=\"container mx-auto p-4 sm:p-6 lg:p-8\">
        <main class=\"bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md\">
            {% if flash.success %}
                <div class=\"bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6\" role=\"alert\">
                    <strong class=\"font-bold\">Success!</strong>
                    <span class=\"block sm:inline\">{{ flash.success }}</span>
                </div>
            {% endif %}
            {% if flash.error %}
                <div class=\"bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6\" role=\"alert\">
                    <strong class=\"font-bold\">Error!</strong>
                    <span class=\"block sm:inline\">{{ flash.error }}</span>
                </div>
            {% endif %}
            {% block content %}{% endblock %}
        </main>
    </div>

    <div id=\"update-modal\" class=\"absolute inset-0 z-50 hidden flex items-center justify-center p-4 bg-black bg-opacity-75\" style=\"background-color: #000000cf;\">
        <div id=\"update-modal-content\" class=\"bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col\">
            <div class=\"flex justify-between bg-pink-700 items-center p-4 border-b dark:border-gray-700\">
                <h3 class=\"text-2xl font-bold text-white\">Update Available</h3>
                <button id=\"close-update-modal\" class=\"text-gray-200 hover:text-gray-700 dark:hover:text-white text-3xl leading-none\">&times;</button>
            </div>
            <div class=\"p-6 overflow-y-auto\">
                <h4 id=\"update-version-tag\" class=\"text-xl font-bold mb-4\"></h4>
                <div class=\"prose dark:prose-invert max-w-none\">
                    <p>The following updates are available. It is recommended to run the update command from your terminal.</p>
                    <pre class=\"command-line\"><code class=\"language-bash\">php rhapsody app:update</code></pre>
                    <hr><br>
                    <h4>Release Notes:</h4>
                    <div id=\"update-release-notes\" class=\"text-sm p-4 bg-gray-200 dark:bg-gray-900 dark:text-gray-200 rounded-md\">
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
            <div class=\"flex justify-end items-center p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700\">
                <button id=\"close-update-modal-footer\" class=\"bg-pink-700  hover:bg-gray-400 text-white font-bold py-2 px-4 rounded\">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- MOBILE MENU LOGIC ---
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
            // --- THEME CYCLER LOGIC ---
            const themeBtn = document.getElementById('theme-btn');
            const themes = ['dark', 'light', 'system'];
            const icons = {
                dark: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\" style=\"color: yellow;\"><path d=\"M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z\" /></svg>`,
                light: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\" style=\"color: orange;\"><path d=\"M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z\"/></svg>`,
                system: `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\"><path fill-rule=\"evenodd\" d=\"M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z\" clip-rule=\"evenodd\" /></svg>`
            };
            const titles = {
                dark: 'Switch to Light Mode',
                light: 'Switch to System Preference',
                system: 'Switch to Dark Mode'
            };

            let currentThemeIndex = 0;

            const applyTheme = (theme) => {
                if (theme === 'system') {
                    localStorage.removeItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    if (systemTheme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } else {
                    localStorage.setItem('theme', theme);
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
                updateButtonUI(theme);
            };

            const updateButtonUI = (theme) => {
                themeBtn.innerHTML = icons[theme];
                themeBtn.title = titles[theme];
                currentThemeIndex = themes.indexOf(theme);
            };

            themeBtn.addEventListener('click', () => {
                currentThemeIndex = (currentThemeIndex + 1) % themes.length;
                const nextTheme = themes[currentThemeIndex];
                applyTheme(nextTheme);
            });

            // Set initial state on page load
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme && themes.includes(savedTheme)) {
                applyTheme(savedTheme);
            } else {
                applyTheme('system'); // Default to system
            }

            // --- UPDATE MODAL LOGIC ---
            const updateLink = document.getElementById('update-notification-link');
            const updateModal = document.getElementById('update-modal');
            
            if (updateLink && updateModal) {
                const closeButtons = document.querySelectorAll('#close-update-modal, #close-update-modal-footer');
                const versionTagEl = document.getElementById('update-version-tag');
                const releaseNotesEl = document.getElementById('update-release-notes');

                updateLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const newVersion = updateLink.dataset.version;
                    versionTagEl.textContent = `New Version: \${newVersion}`;
                    releaseNotesEl.innerHTML = '<p>Loading release notes...</p>';
                    updateModal.classList.remove('hidden');

                    fetch('https://api.github.com/repos/arout77/rhapsody/releases/latest')
                        .then(response => response.json())
                        .then(data => {
                            if (data.body) {
                                releaseNotesEl.innerHTML = `<pre class=\"whitespace-pre-wrap text-sm\">\${data.body}</pre>`;
                            } else {
                                releaseNotesEl.textContent = 'No release notes were provided for this version.';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching release notes:', error);
                            releaseNotesEl.textContent = 'Could not load release notes.';
                        });
                });

                const closeModal = () => {
                    updateModal.classList.add('hidden');
                };

                closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
                
                updateModal.addEventListener('click', (e) => {
                    if (e.target === updateModal) {
                        closeModal();
                    }
                });
            }
        });
    </script>
</body>
</html>", "layouts/main.twig", "C:\\MAMP\\htdocs\\dugout\\views\\themes\\americana\\layouts\\main.twig");
    }
}

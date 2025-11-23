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

/* nav.twig */
class __TwigTemplate_3fedbd9c94317b71051bca58e56c9a3a extends Template
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
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<!-- Header, Main Content, Footer -->
<header class=\"bg-indigo-900 text-white shadow-md sticky top-0 z-50\">
    <div class=\"container mx-auto px-4 sm:px-6 lg:px-8\">
        <div class=\"flex items-center justify-between h-16\">
            <!-- Logo -->
            <div class=\"flex-shrink-0\">
                <a href=\"";
        // line 7
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
        yield "/\" class=\"flex items-center space-x-3 group text-2xl font-bold tracking-wider logo text-white hover:text-gray-200 transition\">
                    ";
        // line 9
        yield "                    <img src=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
        yield "/public/img/logo.webp\" class=\"h-10 w-auto\" alt=\"Dugout Dynasty\">
                    ";
        // line 11
        yield "                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class=\"hidden md:flex md:items-center md:space-x-6\">
                ";
        // line 16
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["auth"] ?? null), "check", [], "any", false, false, false, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 17
            yield "                    <!-- Franchise Dropdown -->
                    <div class=\"relative group\">
                        <button class=\"nav-link flex items-center text-white hover:text-blue-200 font-medium focus:outline-none transition\">
                            <span>Franchise</span>
                            <svg class=\"w-4 h-4 ml-1 transform group-hover:rotate-180 transition\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 9l-7 7-7-7\"></path></svg>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class=\"absolute left-0 mt-2 w-56 bg-white rounded-md shadow-xl py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top-left z-50 border border-gray-100\">
                            <div class=\"px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider\">Management</div>
                            <a href=\"";
            // line 26
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/draft\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Draft Room</a>
                            <a href=\"";
            // line 27
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/clubhouse\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Clubhouse & Lineups</a>
                            
                            <div class=\"border-t border-gray-100 my-1\"></div>
                            <div class=\"px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider\">Action</div>
                            <a href=\"";
            // line 31
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/pregame\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Play Ball!</a>
                            
                            <div class=\"border-t border-gray-100 my-1\"></div>
                            <a href=\"";
            // line 34
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/new-game\" class=\"block px-4 py-2 text-sm text-red-600 hover:bg-red-50\">Start New Career</a>
                        </div>
                    </div>

                    <!-- Right Side Auth -->
                    <div class=\"ml-4 flex items-center space-x-4 border-l border-indigo-700 pl-6\">
                         <span class=\"text-sm text-indigo-200\">GM Mode</span>
                         <a href=\"";
            // line 41
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/logout\" class=\"text-white hover:text-red-300 transition text-sm font-medium\">Logout</a>
                    </div>
                    <div class=\"flex items-center space-x-4\">
                        ";
            // line 53
            yield "                        <div id=\"theme-switcher\" class=\"p-1\">
                            <button id=\"theme-btn\" class=\"p-2 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600\">
                                </button>
                        </div>

                        <div class=\"md:hidden flex items-center\">
                            <button id=\"mobile-menu-button\" class=\"inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100/30 dark:hover:bg-gray-700/30 focus:outline-none\">
                                <svg class=\"h-6 w-6\" stroke=\"currentColor\" fill=\"none\" viewBox=\"0 0 24 24\">
                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16m-7 6h7\" />
                                </svg>
                            </button>
                        </div>
                    </div>
                ";
        } else {
            // line 67
            yield "                    <a href=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/login\" class=\"text-white hover:text-indigo-200 font-medium transition\">Login</a>
                    <a href=\"";
            // line 68
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/register\" class=\"bg-white text-indigo-900 hover:bg-gray-100 px-4 py-2 rounded-md font-bold shadow-sm transition\">Get Started</a>
                ";
        }
        // line 70
        yield "            </nav>

            <!-- Mobile menu button -->
            <div class=\"md:hidden flex items-center\">
                <button id=\"mobile-menu-btn\" class=\"text-gray-300 hover:text-white focus:outline-none p-2\">
                    <svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16M4 18h16\"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id=\"mobile-menu\" class=\"hidden md:hidden bg-indigo-800 border-t border-indigo-700\">
        <div class=\"px-2 pt-2 pb-3 space-y-1 sm:px-3\">
            ";
        // line 86
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["auth"] ?? null), "check", [], "any", false, false, false, 86)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 87
            yield "                <div class=\"px-3 py-2 text-xs font-bold text-indigo-300 uppercase\">Franchise</div>
                <a href=\"";
            // line 88
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/draft\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Draft Room</a>
                <a href=\"";
            // line 89
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/clubhouse\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Clubhouse</a>
                <a href=\"";
            // line 90
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/pregame\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Play Ball!</a>
                <a href=\"";
            // line 91
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/new-game\" class=\"block px-3 py-2 rounded-md text-base font-medium text-red-300 hover:bg-indigo-700\">New Career</a>
                
                <div class=\"border-t border-indigo-700 my-2\"></div>
                <a href=\"";
            // line 94
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/logout\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-red-600\">Logout</a>
            ";
        } else {
            // line 96
            yield "                <a href=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/login\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Login</a>
                <a href=\"";
            // line 97
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["base_url"] ?? null), "html", null, true);
            yield "/register\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Register</a>
            ";
        }
        // line 99
        yield "        </div>
    </div>
</header>

<script>
    // Simple Mobile Menu Toggle
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    if(btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    }
</script>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "nav.twig";
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
        return array (  194 => 99,  189 => 97,  184 => 96,  179 => 94,  173 => 91,  169 => 90,  165 => 89,  161 => 88,  158 => 87,  156 => 86,  138 => 70,  133 => 68,  128 => 67,  112 => 53,  106 => 41,  96 => 34,  90 => 31,  83 => 27,  79 => 26,  68 => 17,  66 => 16,  59 => 11,  54 => 9,  50 => 7,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<!-- Header, Main Content, Footer -->
<header class=\"bg-indigo-900 text-white shadow-md sticky top-0 z-50\">
    <div class=\"container mx-auto px-4 sm:px-6 lg:px-8\">
        <div class=\"flex items-center justify-between h-16\">
            <!-- Logo -->
            <div class=\"flex-shrink-0\">
                <a href=\"{{ base_url }}/\" class=\"flex items-center space-x-3 group text-2xl font-bold tracking-wider logo text-white hover:text-gray-200 transition\">
                    {# Use a baseball icon or your logo image #}
                    <img src=\"{{ base_url }}/public/img/logo.webp\" class=\"h-10 w-auto\" alt=\"Dugout Dynasty\">
                    {# <span>Dugout Dynasty</span> #}
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class=\"hidden md:flex md:items-center md:space-x-6\">
                {% if auth.check %}
                    <!-- Franchise Dropdown -->
                    <div class=\"relative group\">
                        <button class=\"nav-link flex items-center text-white hover:text-blue-200 font-medium focus:outline-none transition\">
                            <span>Franchise</span>
                            <svg class=\"w-4 h-4 ml-1 transform group-hover:rotate-180 transition\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 9l-7 7-7-7\"></path></svg>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class=\"absolute left-0 mt-2 w-56 bg-white rounded-md shadow-xl py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top-left z-50 border border-gray-100\">
                            <div class=\"px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider\">Management</div>
                            <a href=\"{{ base_url }}/draft\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Draft Room</a>
                            <a href=\"{{ base_url }}/clubhouse\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Clubhouse & Lineups</a>
                            
                            <div class=\"border-t border-gray-100 my-1\"></div>
                            <div class=\"px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider\">Action</div>
                            <a href=\"{{ base_url }}/pregame\" class=\"block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700\">Play Ball!</a>
                            
                            <div class=\"border-t border-gray-100 my-1\"></div>
                            <a href=\"{{ base_url }}/new-game\" class=\"block px-4 py-2 text-sm text-red-600 hover:bg-red-50\">Start New Career</a>
                        </div>
                    </div>

                    <!-- Right Side Auth -->
                    <div class=\"ml-4 flex items-center space-x-4 border-l border-indigo-700 pl-6\">
                         <span class=\"text-sm text-indigo-200\">GM Mode</span>
                         <a href=\"{{ base_url }}/logout\" class=\"text-white hover:text-red-300 transition text-sm font-medium\">Logout</a>
                    </div>
                    <div class=\"flex items-center space-x-4\">
                        {# <div class=\"hidden md:flex items-center space-x-4\">
                            {% if auth.check %}
                                 <a href=\"{{ base_url }}/dashboard\" class=\"nav-link\">Dashboard</a>
                                 <a href=\"{{ base_url }}/logout\" class=\"nav-link\">Logout</a>
                            {% else %}
                                 <a href=\"{{ base_url }}/login\" class=\"nav-link\">Login</a>
                                 <a href=\"{{ base_url }}/register\" class=\"bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md text-sm transition-colors\">Register</a>
                            {% endif %}
                        </div> #}
                        <div id=\"theme-switcher\" class=\"p-1\">
                            <button id=\"theme-btn\" class=\"p-2 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600\">
                                </button>
                        </div>

                        <div class=\"md:hidden flex items-center\">
                            <button id=\"mobile-menu-button\" class=\"inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100/30 dark:hover:bg-gray-700/30 focus:outline-none\">
                                <svg class=\"h-6 w-6\" stroke=\"currentColor\" fill=\"none\" viewBox=\"0 0 24 24\">
                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16m-7 6h7\" />
                                </svg>
                            </button>
                        </div>
                    </div>
                {% else %}
                    <a href=\"{{ base_url }}/login\" class=\"text-white hover:text-indigo-200 font-medium transition\">Login</a>
                    <a href=\"{{ base_url }}/register\" class=\"bg-white text-indigo-900 hover:bg-gray-100 px-4 py-2 rounded-md font-bold shadow-sm transition\">Get Started</a>
                {% endif %}
            </nav>

            <!-- Mobile menu button -->
            <div class=\"md:hidden flex items-center\">
                <button id=\"mobile-menu-btn\" class=\"text-gray-300 hover:text-white focus:outline-none p-2\">
                    <svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16M4 18h16\"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id=\"mobile-menu\" class=\"hidden md:hidden bg-indigo-800 border-t border-indigo-700\">
        <div class=\"px-2 pt-2 pb-3 space-y-1 sm:px-3\">
            {% if auth.check %}
                <div class=\"px-3 py-2 text-xs font-bold text-indigo-300 uppercase\">Franchise</div>
                <a href=\"{{ base_url }}/draft\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Draft Room</a>
                <a href=\"{{ base_url }}/clubhouse\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Clubhouse</a>
                <a href=\"{{ base_url }}/pregame\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Play Ball!</a>
                <a href=\"{{ base_url }}/new-game\" class=\"block px-3 py-2 rounded-md text-base font-medium text-red-300 hover:bg-indigo-700\">New Career</a>
                
                <div class=\"border-t border-indigo-700 my-2\"></div>
                <a href=\"{{ base_url }}/logout\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-red-600\">Logout</a>
            {% else %}
                <a href=\"{{ base_url }}/login\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Login</a>
                <a href=\"{{ base_url }}/register\" class=\"block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-700\">Register</a>
            {% endif %}
        </div>
    </div>
</header>

<script>
    // Simple Mobile Menu Toggle
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    if(btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    }
</script>", "nav.twig", "C:\\MAMP\\htdocs\\dugout\\views\\themes\\americana\\nav.twig");
    }
}

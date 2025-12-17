<?php
/**
 * @brief Class with methods to display the preference page and helpful functions.
 *
 * This class adds some functions that are used in the Documents-preferences module to keep the
 * code easy to read and short
 *
 * ToolsPreferencesPresenter is a modified (Admidio)PreferencesPresenter
 *
 * **Code example**
 * ```
 * // generate html output
 * $page = new ToolsPreferencesPresenter('Options', $headline);
 * $page->createSettingsForm();
 * $page->show();
 * ```
 * @copyright rmb
 * @see https://github.com/rmbinder/tools/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */

namespace Plugins\Tools\classes\Presenter;

use Admidio\Changelog\Service\ChangelogService;
use Admidio\Documents\Service\DocumentsService;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\Tools\classes\Config\ConfigTable;

class ToolsPreferencesPresenter extends PagePresenter
{
    /**
     * @var array Array with all possible entries for the preferences.
     *            Each entry consists of an array that has the following structure:
     *            array ('key' => 'xzy', 'label' => 'xyz', 'panels' => array('id' => 'xyz', 'title' => 'xyz', 'icon' => 'xyz'))
     * 
     *            There are thwo different visualizations of the preferences:
     *              1) a nested tab structure (main tabs created by 'key' and 'label' and sub tabs created by 'panels')
     *              2) a accordion structure when the @media query (max-width: 768px) is active ('key' and 'label' are used for card header
     *                 and 'panels' for accordions inside the card) 
     */
    protected array $preferenceTabs = array();
    /**
     * @var string Name of the preference panel that should be shown after page loading.
     *             If this parameter is empty, then show the common preferences.
     */
    protected string $preferencesPanelToShow = '';

    /**
     * Constructor that initializes the class member parameters
     * @throws Exception
     */
    public function __construct(string $panel = '')
    {
        global $gL10n;

        $this->initialize();
        $this->setPanelToShow($panel);

        $this->setHtmlID('adm_preferences');
        $this->setHeadline($gL10n->get('SYS_SETTINGS'));

        parent::__construct();
    }

    /**
     * @throws Exception
     */
    private function initialize(): void
    {
        global $gL10n;
        $this->preferenceTabs = array(
           
            // === 1) System ===
            array(
                'key'    => 'system',
                'label'  => '',
                'panels' => array(
                    array('id'=>'settings',  'title'=>$gL10n->get('PLG_TOOLS_NAME'),   'icon'=>'bi-gear',     'subcards'=>false),           
                ),
            ),
        );
    }

    /**
     * Generates the html of the form from the options preferences and will return the complete html.
     * @return string Returns the complete html of the form from the options preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createSettingsForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gDb, $gProfileFields;
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $formSettings = new FormPresenter(
            'adm_preferences_form_settings',
            '../templates/preferences.plugin.tools.tpl',
            SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/Tools/system/preferences.php', array('mode' => 'save', 'panel' => 'Settings')),
            null,
            array('class' => 'form-preferences')
        );
        
        $existingPlugins = getExistingPlugins();
        
        foreach ($existingPlugins as $pluginKey => $pluginData)
        {
            $formSettings->addCheckbox(
                $pluginData['name'],
                $pluginData['name'],
                $pluginData['enabled']
            );
        }
 
        $html = '<a class="btn btn-primary" href="' . ADMIDIO_URL . FOLDER_PLUGINS . '/Tools/system/uninstall.php">
                    <i class="bi bi-trash"></i>' . $gL10n->get('PLG_TOOLS_UNINSTALLATION') . '
                </a>';
        $formSettings->addCustomContent(
            'openUninstallation',
            $gL10n->get('PLG_TOOLS_UNINSTALLATION'),
            $html,
            array('helpTextId' => 'PLG_TOOLS_UNINSTALLATION_DESC')
        );
        
        $formSettings->addSubmitButton(
            'adm_button_save_options',
            $gL10n->get('SYS_SAVE'),
            array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3')
        );
        
        $this->assignSmartyVariable('existingPlugins', $existingPlugins);
        $smarty = $this->getSmartyTemplate();
        $formSettings->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formSettings);
        return $smarty->fetch('../templates/preferences.plugin.tools.tpl');
    }
    
    /**
     * Set a panel name that should be opened at a page load.
     * @param string $panelName Name of the panel that should be opened at a page load.
     * @return void
     */
    public function setPanelToShow(string $panelName): void
    {
        $this->preferencesPanelToShow = $panelName;
    }

    /**
     * Read all available registrations from the database and create the HTML content of this
     * page with the Smarty template engine and write the HTML output to the internal
     * parameter **$pageContent**. If no registration is found, then show a message to the user.
     */
    public function show(): void
    {
        global $gL10n;

        if ($this->preferencesPanelToShow !== '') {
            // open the selected panel
            if ($this->preferencesPanelToShow !== '') {
                $this->addJavascript('
                    // --- Reset Tab active states for large screens
                    $("#adm_preferences_tabs .nav-link").removeClass("active");
                    $("#adm_preferences_tab_content .tab-pane").removeClass("active show");

                    // --- Reset Accordion active states for small screens
                    $("#adm_preferences_accordion [aria-expanded=\'true\']").attr("aria-expanded", "false");
                    $("#adm_preferences_accordion .accordion-button").addClass("collapsed");
                    $("#adm_preferences_accordion .accordion-item").removeClass("show");
                    $("#adm_preferences_accordion .accordion-collapse").removeClass("show");
                    
                    // --- Activate the selected Tab and its content
                    $("#adm_tab_' . $this->preferencesPanelToShow . '").addClass("active");
                    $("#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    
                    // --- For Mobile Accordion: open the desired accordion panel
                    $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show");
                                        
                    // --- Desktop vs. Mobile via jQuery visibility
                    if ($(".d-none.d-md-block").is(":visible")) {
                        // Desktop mode
                        $("#adm_preferences_tabs .nav-link[data-bs-target=\'#adm_tab_' . $this->preferencesPanelToShow . '_content\']").addClass("active");
                        $("#adm_preferences_tab_content .tab-pane#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    } else {
                        // Mobile mode
                        $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show").attr("aria-expanded", "true");
                        $("#heading_' . $this->preferencesPanelToShow . ' .accordion-button").removeClass("collapsed").attr("aria-expanded", "true");
                        // --- Hash setzen, damit Bookmark/Scroll stimmt und zum Element scrollen
                        location.hash = "#heading_' . $this->preferencesPanelToShow . '";
                    }
                ', true);
            }
        }

        $this->addJavascript('
            // === 1) Panel laden und Events binden ===
            function loadPreferencesPanel(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");
                if (!panelContainer.length) return;

                // Schritt 1: Spinner einfügen
                panelContainer.html("<div class=\"d-flex justify-content-center align-items-center\" style=\"height: 200px;\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Lade...</span></div></div>");

                $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/Tools/system/preferences.php", {
                    mode: "html_form",
                    panel: panelId
                }, function(htmlContent) {
                    panelContainer.html(htmlContent);
                    initializePanelInteractions(panelId);
                }).fail(function() {
                    panelContainer.html("<div class=\"text-danger\">Fehler beim Laden</div>");
                });
            }
        
            // === 2) Innerhalb eines Panels die Klick-Handler anmelden ===
            function initializePanelInteractions(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");
            
                // Captcha-Refresh
                panelContainer.off("click", "#adm_captcha_refresh").on("click", "#adm_captcha_refresh", function(event) {
                    event.preventDefault();
                    var captchaImg = panelContainer.find("#adm_captcha");
                    if (captchaImg.length) {
                        captchaImg.attr("src", "' . ADMIDIO_URL . FOLDER_LIBS . '/securimage/securimage_show.php" + "?" + Math.random());
                    }
                });
            
                // Update-Check
                panelContainer.off("click", "#adm_link_check_update").on("click", "#adm_link_check_update", function(event) {
                    event.preventDefault();
                    var versionInfoContainer = panelContainer.find("#adm_version_content");
                    versionInfoContainer.html("<i class=\"spinner-border spinner-border-sm\"></i>").show();
                    $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/Tools/system/preferences.php", { mode: "update_check" }, function(htmlVersion) {
                        versionInfoContainer.html(htmlVersion);
                    });
                });
            
                // Verzeichnis-Schutz prüfen
                panelContainer.off("click", "#link_directory_protection").on("click", "#link_directory_protection", function(event) {
                    event.preventDefault();
                    var statusContainer = panelContainer.find("#directory_protection_status");
                    statusContainer.html("<i class=\"spinner-border spinner-border-sm\"></i>").show();
                    $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . '/Tools/system/preferences.php", { mode: "htaccess" }, function(statusText) {
                        var directoryProtection = panelContainer.find("#directoryProtection");
                        directoryProtection.html("<span class=\"text-success\"><strong>" + statusText + "</strong></span>");
                    });
                });
               
                // Module Settings visibility
                // Universal handling for module enabled toggle within the current panel container
                
                // define additional ids that should also be considered for visibility toggling
                var additionalIds = [\'#system_notifications_enabled\'];
                // Look for any input whose id ends with "_module_enabled"
                var selectors = ["[id$=\'_module_enabled\']"].concat(additionalIds);

                var moduleEnabledField = panelContainer.find(selectors.join(", ")).filter(":visible");
                if (moduleEnabledField.length > 0) {
                    // Get all row elements inside the form, excluding the row containing the module enabled field
                    var formElementGroups = panelContainer.find("form div.row")
                        .not(moduleEnabledField.closest("div.row"));
                    
                    // Function to update visibility based on the fields type and state
                    var updateVisibility = function(initialCall) {
                        var isEnabled;
                        if (moduleEnabledField.attr("type") === "checkbox") {
                            isEnabled = moduleEnabledField.is(":checked");
                        } else {
                            isEnabled = moduleEnabledField.val() != 0;
                        }
                        
                        if (initialCall === true) {
                            if (isEnabled) {
                                formElementGroups.show();
                            } else {
                                formElementGroups.hide();
                            }
                        } else {
                            if (isEnabled) {
                                formElementGroups.slideDown("slow");
                            } else {
                                formElementGroups.slideUp("slow");
                            }
                        }
                    };
                    
                    // Set initial state without animation
                    updateVisibility(true);
                    
                    // Update visibility on change
                    moduleEnabledField.on("change", updateVisibility);
                }
            }
        
            // === 3) Hooks für Desktop-Tabs ===
            $(document).on("shown.bs.tab", "ul#adm_preferences_tabs button.nav-link", function(e) {
                var target = e.target.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
                // scroll to the top of the page
                $("html, body").animate({
                    scrollTop: 0
                }, 500);
            });
            // initial: load the active tab panel
            $("ul#adm_preferences_tabs button.nav-link.active").each(function() {
                var target = this.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
            });
        
            // === 4) Hooks für Mobile-Accordion ===
            $(document).on("shown.bs.collapse", "#adm_preferences_accordion .accordion-collapse", function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });
            // initial: geöffnetes Accordion-Panel laden
            $("#adm_preferences_accordion .accordion-collapse.show").each(function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });
        
            // === 5) Formular-Submit per AJAX ===
            $(document).on("submit", "form[id^=\"adm_preferences_form_\"]", formSubmit);
      ', true);
      
        ChangelogService::displayHistoryButton($this, 'preferences', 'preferences,texts');

        // Load the select2 in case any of the form uses a select box. Unfortunately, each section
        // is loaded on-demand, when there is no HTML page anymore to insert the css/JS file loading,
        // so we need to do it here, even when no selectbox will be used...
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/css/select2.css');
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2-bootstrap-theme/select2-bootstrap-5-theme.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/select2.js');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/i18n/' . $gL10n->getLanguageLibs() . '.js');

        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/css/bootstrap-tabs-x-admidio.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/js/bootstrap-tabs-x-admidio.js');

        $this->assignSmartyVariable('preferenceTabs', $this->preferenceTabs);
        $this->addTemplateFile('preferences/preferences.tpl');

        parent::show();
    }
}

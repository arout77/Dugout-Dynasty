<?php
// core/Toolbar.php

namespace Core;

class Toolbar
{
    /**
     * @param array $data
     */
    public function __construct( protected array $data )
    {}
    public function render(): string
    {
        // --- Data Preparation for Toolbar Header ---
        $appVersion   = htmlspecialchars( $this->data['app_version'] ?? 'N/A', ENT_QUOTES, 'UTF-8' );
        $execTime     = $this->data['execution_time'] ?? '0';
        $memUsage     = $this->data['memory_usage'] ?? '0';
        $responseCode = $this->data['response_code'] ?? 'N/A';
        $queryCount   = count( $this->data['queries'] ?? [] );

        // --- Data Preparation for Toolbar Panels (in PHP) ---

        // Format SQL queries
        $queriesHtml = '';
        if ( $queryCount > 0 ) {
            foreach ( $this->data['queries'] as $query ) {
                $sql        = htmlspecialchars( $query['sql'], ENT_QUOTES, 'UTF-8' );
                $params     = htmlspecialchars( json_encode( $query['params'] ), ENT_QUOTES, 'UTF-8' );
                $time       = round( $query['executionMS'] * 1000, 2 );
                $callerFile = $query['caller']['file'] ?? 'N/A';
                $callerLine = $query['caller']['line'] ?? '-';

                $queriesHtml .= "<div class='query-item'>
                <div class='query-sql'>{$sql}</div>
                <div class='query-meta'>
                    <span>Params: {$params}</span>
                    <span>Time: {$time}ms</span>
                    <span style='margin-left: auto;'>{$callerFile}:{$callerLine}</span>
                </div>
            </div>";
            }
        } else {
            $queriesHtml = '<p>No queries were executed for this request.</p>';
        }

        // Build the panel content array
        $panels_data = [
            'panel-request' => '<h3>Request / Route</h3><pre>' . htmlspecialchars( json_encode( $this->data['route'] ?? 'No route matched', JSON_PRETTY_PRINT ), ENT_QUOTES, 'UTF-8' ) . '</pre>',
            'panel-logs'    => '<h3>PHP Error Log</h3><pre>' . ( $this->data['logs']['php'] ?? 'Log not available.' ) . '</pre><h3>Apache Error Log</h3><pre>' . ( $this->data['logs']['apache'] ?? 'Log not available.' ) . '</pre>',
            'panel-db'      => '<h3>Database Queries</h3><div>' . $queriesHtml . '</div>',
            'panel-session' => '<h3>Session Data</h3><pre>' . htmlspecialchars( json_encode( $this->data['session'] ?? [], JSON_PRETTY_PRINT ), ENT_QUOTES, 'UTF-8' ) . '</pre>'
        ];
        // Safely encode the array for JavaScript
        $panels_json = json_encode( $panels_data );

        // --- HEREDOC for HTML, CSS, JS ---
        return <<<HTML
<style>
    #rhapsody-debug-toolbar { position: fixed; bottom: 0; left: 0; width: 100%; background-color: #111827; color: #F9FAFB; z-index: 99999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; box-shadow: 0 -2px 10px rgba(0,0,0,0.3); }
    #rhapsody-debug-toolbar-header { display: flex; align-items: stretch; height: 40px; }
    #rhapsody-debug-toolbar .toolbar-item { display: flex; align-items: center; padding: 0 15px; border-right: 1px solid #374151; cursor: pointer; }
    #rhapsody-debug-toolbar .toolbar-item:hover { background-color: #1F2937; }
    #rhapsody-debug-toolbar .toolbar-item.active { background-color: #374151; }
    #rhapsody-debug-toolbar .toolbar-label { font-weight: 600; margin-right: 8px; }
    #rhapsody-debug-toolbar .toolbar-value { color: #9CA3AF; }
    #rhapsody-debug-toolbar .toolbar-logo { font-weight: bold; background: #4f46e5; }
    #rhapsody-debug-toolbar .status-ok { color: #10B981; }
    #rhapsody-debug-toolbar-panel { display: none; padding: 20px; background-color: #1F2937; border-top: 1px solid #374151; max-height: 40vh; overflow-y: auto; }
    #rhapsody-debug-toolbar-panel h3 { font-size: 1.5rem; font-weight: bold; border-bottom: 1px solid #4B5563; padding-bottom: 10px; margin: 0 0 15px 0; }
    #rhapsody-debug-toolbar-panel pre { background: #111827; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-break: break-all; }
    .query-item { border-bottom: 1px solid #374151; padding: 10px 0; }
    .query-item:last-child { border-bottom: none; }
    .query-sql { font-family: monospace; color: #A5B4FC; margin-bottom: 5px; }
    .query-meta { font-size: 12px; color: #6B7280; }
    .query-meta span { margin-right: 15px; }
</style>

<div id="rhapsody-debug-toolbar">
    <div id="rhapsody-debug-toolbar-panel"></div>
    <div id="rhapsody-debug-toolbar-header">
        <div class="toolbar-item toolbar-logo" id="toolbar-close-btn">Rhapsody {$appVersion}</div>
        <div class="toolbar-item" data-panel="panel-request"><span class="toolbar-label">Request</span> <span class="toolbar-value status-ok">{$responseCode}</span></div>
        <div class="toolbar-item" data-panel="panel-logs"><span class="toolbar-label">Logs</span></div>
        <div class="toolbar-item" data-panel="panel-db"><span class="toolbar-label">Database</span> <span class="toolbar-value">{$queryCount} Queries</span></div>
        <div class="toolbar-item" data-panel="panel-session"><span class="toolbar-label">Session</span></div>
        <div class="toolbar-item" style="margin-left: auto; border-right: none;"><span class="toolbar-label">Time:</span> <span class="toolbar-value">{$execTime} ms</span> / <span class="toolbar-label">Memory:</span> <span class="toolbar-value">{$memUsage} MB</span></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toolbar = document.getElementById('rhapsody-debug-toolbar');
        const panel = document.getElementById('rhapsody-debug-toolbar-panel');
        const items = document.querySelectorAll('#rhapsody-debug-toolbar-header .toolbar-item[data-panel]');
        const closeBtn = document.getElementById('toolbar-close-btn');
        let activePanel = null;

        // Safely parse the JSON object created in PHP
        const panels = {$panels_json};

        items.forEach(item => {
            item.addEventListener('click', () => {
                const panelId = item.getAttribute('data-panel');
                if (activePanel === panelId) {
                    panel.style.display = 'none';
                    activePanel = null;
                    item.classList.remove('active');
                } else {
                    panel.innerHTML = panels[panelId];
                    panel.style.display = 'block';
                    activePanel = panelId;
                    items.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                }
            });
        });

        closeBtn.addEventListener('click', (e) => {
            if (e.target === closeBtn) {
                 panel.style.display = 'none';
                 activePanel = null;
                 items.forEach(i => i.classList.remove('active'));
            }
        });
    });
</script>
HTML;
    }
}

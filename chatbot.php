<div id="bamstay-chat-widget" class="chat-widget">
    <section id="bamstay-chat-panel" class="chat-panel" role="dialog" aria-modal="false" aria-labelledby="bamstay-chat-title" hidden>
        <header class="chat-header">
            <div class="chat-heading">
                <span class="chat-avatar" aria-hidden="true"><?php icon('home', 'h-5 w-5'); ?></span>
                <span>
                    <strong id="bamstay-chat-title">BAMStay Assistant</strong>
                    <span class="chat-subtitle">Ask us about the dormitory</span>
                </span>
            </div>
            <button id="bamstay-chat-close" class="chat-icon-button" type="button" aria-label="Close chat"><?php icon('close', 'h-5 w-5'); ?></button>
        </header>
        <div id="bamstay-chat-messages" class="chat-messages" role="log" aria-live="polite" aria-relevant="additions text">
            <div class="chat-bubble chat-bubble-assistant">Hi! I’m the BAMStay Assistant. Ask me about our rooms, bedspaces, rates, facilities, or location.</div>
            <div class="chat-suggestions" aria-label="Suggested questions">
                <button class="chat-suggestion" type="button">Magkano ang monthly rate?</button>
                <button class="chat-suggestion" type="button">Anong facilities ang meron?</button>
                <button class="chat-suggestion" type="button">Saan matatagpuan ang BAMStay?</button>
                <button class="chat-suggestion" type="button">Anong beds ang available sa Room 3?</button>
            </div>
        </div>
        <form id="bamstay-chat-form" class="chat-form">
            <label for="bamstay-chat-input" class="sr-only">Your message</label>
            <input id="bamstay-chat-input" type="text" maxlength="1000" autocomplete="off" placeholder="Type your message..." required>
            <button id="bamstay-chat-send" class="chat-send-button" type="submit" aria-label="Send message"><?php icon('arrow-right', 'h-5 w-5'); ?></button>
        </form>
        <p class="chat-disclaimer">Replies use approved public information only.</p>
    </section>

    <button id="bamstay-chat-toggle" class="chat-launcher" type="button" aria-label="Open BAMStay Assistant" aria-expanded="false" aria-controls="bamstay-chat-panel">
        <span class="chat-launcher-icon chat-launcher-open" aria-hidden="true"><?php icon('message', 'h-6 w-6'); ?></span>
        <span class="chat-launcher-icon chat-launcher-close" aria-hidden="true"><?php icon('close', 'h-6 w-6'); ?></span>
        <span class="sr-only">BAMStay Assistant</span>
    </button>
</div>
<script type="application/json" id="bamstay-chat-config"><?= json_encode(
    ['endpoint' => 'api/chat.php', 'csrfToken' => $_SESSION['csrf_token']],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
) ?></script>

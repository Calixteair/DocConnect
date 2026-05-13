import { Controller } from '@hotwired/stimulus';

/**
 * Widget chatbot d'orientation DocConnect.
 *
 * UX :
 *  - Bouton flottant en bas à droite (FAB).
 *  - Clic → panneau s'ouvre (380x520 desktop / fullscreen mobile).
 *  - Au load du panneau, fetch /api/chat/history pour recharger la session.
 *  - Submit → POST /api/chat/message → réponse complète affichée.
 *  - Indicateur "écrit…" pendant la requête.
 *  - Markdown léger : **gras**, *italique*, listes, line breaks.
 */
export default class extends Controller {
    static targets = ['panel', 'fab', 'messages', 'input', 'form', 'submit', 'typing'];

    connect() {
        this.isLoading = false;
        this.historyLoaded = false;
        this.boundEscape = (e) => { if (e.key === 'Escape' && this.isOpen()) this.close(); };
        document.addEventListener('keydown', this.boundEscape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundEscape);
    }

    toggle() {
        this.isOpen() ? this.close() : this.open();
    }

    async open() {
        this.panelTarget.dataset.open = 'true';
        this.fabTarget.setAttribute('aria-expanded', 'true');
        this.inputTarget.focus();

        if (!this.historyLoaded) {
            await this.loadHistory();
            this.historyLoaded = true;
        }
        this.scrollToBottom();
    }

    close() {
        this.panelTarget.dataset.open = 'false';
        this.fabTarget.setAttribute('aria-expanded', 'false');
        this.fabTarget.focus();
    }

    isOpen() {
        return this.panelTarget.dataset.open === 'true';
    }

    async loadHistory() {
        try {
            const res = await fetch('/api/chat/history', { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach((m) => this.renderMessage(m.role, m.content, false));
            } else {
                // Premier visiteur : disclaimer.
                this.renderMessage('assistant',
                    "Bonjour, je suis l'assistant DocConnect. Décrivez votre besoin et je vous oriente vers la bonne spécialité.\n\n_Je suis un assistant d'orientation, pas un médecin. En cas d'urgence, composez le 190._",
                    false,
                );
            }
        } catch (e) {
            this.renderMessage('assistant', "Impossible de charger l'historique de conversation.", false);
        }
    }

    async submit(event) {
        event.preventDefault();
        const text = this.inputTarget.value.trim();
        if (!text || this.isLoading) return;

        this.renderMessage('user', text, true);
        this.inputTarget.value = '';
        this.setLoading(true);

        let assistantBubble = null;
        let assistantSource = null;
        let assistantText = '';
        let firstChunkReceived = false;

        const ensureBubble = () => {
            if (!assistantBubble) {
                assistantBubble = this.createAssistantBubble(assistantSource);
                this.messagesTarget.appendChild(assistantBubble.node);
            }
        };

        try {
            const res = await fetch('/api/chat/stream', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
                credentials: 'same-origin',
                body: JSON.stringify({ message: text }),
            });

            if (!res.ok || !res.body) {
                const fallback = await res.json().catch(() => ({}));
                this.renderMessage('assistant', fallback.message || 'Erreur du serveur.', true);
                return;
            }

            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });

                let sep;
                while ((sep = buffer.indexOf('\n\n')) !== -1) {
                    const rawEvent = buffer.slice(0, sep);
                    buffer = buffer.slice(sep + 2);

                    const parsed = this.parseSseEvent(rawEvent);
                    if (!parsed) continue;
                    const { event: eventType, data } = parsed;

                    if (eventType === 'meta') {
                        assistantSource = data.source;
                    } else if (eventType === 'chunk') {
                        if (!firstChunkReceived) {
                            firstChunkReceived = true;
                            this.typingTarget.hidden = true;
                        }
                        ensureBubble();
                        assistantText += data.content || '';
                        assistantBubble.body.innerHTML = this.renderMarkdown(assistantText);
                        this.scrollToBottom();
                    } else if (eventType === 'done') {
                        // Rien à faire de plus.
                    } else if (eventType === 'error') {
                        ensureBubble();
                        assistantBubble.body.innerHTML = this.renderMarkdown(data.message || 'Une erreur est survenue.');
                    }
                }
            }
        } catch (e) {
            ensureBubble();
            assistantBubble.body.textContent = 'Connexion interrompue.';
        } finally {
            this.setLoading(false);
        }
    }

    parseSseEvent(raw) {
        const lines = raw.split('\n');
        let event = 'message';
        let data = '';
        for (const line of lines) {
            if (line.startsWith('event:')) event = line.slice(6).trim();
            else if (line.startsWith('data:')) data += line.slice(5).trim();
        }
        if (!data) return null;
        try {
            return { event, data: JSON.parse(data) };
        } catch {
            return null;
        }
    }

    createAssistantBubble(source) {
        const node = document.createElement('div');
        node.className = 'chat-msg chat-msg--assistant';
        const body = document.createElement('div');
        body.className = 'chat-msg__bubble';
        node.appendChild(body);

        if (source) {
            const tag = document.createElement('span');
            tag.className = `chat-msg__source chat-msg__source--${source}`;
            tag.textContent = source === 'llm' ? 'LLM' : (source === 'cache' ? 'cache' : 'intent');
            node.appendChild(tag);
        }
        return { node, body };
    }

    setLoading(loading) {
        this.isLoading = loading;
        this.submitTarget.disabled = loading;
        this.inputTarget.disabled = loading;
        this.typingTarget.hidden = !loading;
        if (this.loadingTimeout) {
            clearTimeout(this.loadingTimeout);
            this.loadingTimeout = null;
        }
        if (loading) {
            // Filet de sécurité : si le stream se bloque, on coupe le typing après 45s.
            this.loadingTimeout = setTimeout(() => this.setLoading(false), 45000);
        } else {
            this.inputTarget.focus();
        }
        this.scrollToBottom();
    }

    renderMessage(role, content, scroll, source) {
        const node = document.createElement('div');
        node.className = `chat-msg chat-msg--${role}`;
        const bubble = document.createElement('div');
        bubble.className = 'chat-msg__bubble';
        bubble.innerHTML = this.renderMarkdown(content);
        node.appendChild(bubble);

        if (source && role === 'assistant') {
            const tag = document.createElement('span');
            tag.className = `chat-msg__source chat-msg__source--${source}`;
            tag.textContent = source === 'llm' ? 'LLM' : (source === 'cache' ? 'cache' : 'intent');
            node.appendChild(tag);
        }

        this.messagesTarget.appendChild(node);
        if (scroll) this.scrollToBottom();
    }

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    /**
     * Markdown minimal — pas de dépendance externe.
     * Supporte : **gras**, *italique*, listes "- item", line breaks.
     * Échappe d'abord le HTML pour éviter XSS depuis les réponses LLM.
     */
    renderMarkdown(text) {
        const escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        let html = escaped
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[^*])\*([^*\n]+?)\*([^*]|$)/g, '$1<em>$2</em>$3')
            .replace(/_([^_\n]+?)_/g, '<em>$1</em>');

        // Liens markdown [label](/path?...) — uniquement URLs internes pour éviter
        // toute redirection vers un site externe (cf. system prompt).
        html = html.replace(/\[([^\]]+)\]\((\/[^)\s]*)\)/g, (_m, label, url) => {
            return `<a href="${url}" class="chat-msg__link">${label}</a>`;
        });

        // Listes "- item" sur des lignes consécutives.
        html = html.replace(/(^|\n)((?:- .+(?:\n|$))+)/g, (_match, prefix, block) => {
            const items = block.trim().split('\n').map((l) => `<li>${l.replace(/^- /, '')}</li>`).join('');
            return `${prefix}<ul>${items}</ul>`;
        });

        // Retours à la ligne restants → <br>.
        return html.replace(/\n/g, '<br>');
    }
}

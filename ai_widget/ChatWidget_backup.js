/**
 * AI Chat Widget - QHTN Fashion Rental
 * Pink Luxury Theme
 * Version 1.0
 */

class AIChartWidget {
    constructor() {
        this.isOpen = false;
        this.isExpanded = false;
        this.messages = [];
        this.isTyping = false;
        this.currentConversationId = null;
        this.input = '';
        
        // Initialize from localStorage
        this.loadFromStorage();
        
        // Render widget
        this.render();
        this.attachEventListeners();
        
        // Auto scroll to newest message
        this.setupAutoScroll();
    }

    /**
     * Render widget HTML
     */
    render() {
        const existingWidget = document.querySelector('.ai-widget-trigger');
        if (existingWidget) return; // Already rendered

        const widgetHTML = `
            <!-- Floating Button -->
            <div class="ai-widget-trigger">
                <button class="ai-widget-button" id="aiWidgetTrigger" title="Hỏi tôi gì">
                    💬
                </button>
            </div>

            <!-- Chat Window -->
            <div class="ai-widget-container" id="aiWidgetContainer">
                <div class="ai-chat-window" id="aiChatWindow">
                    <!-- Header -->
                    <div class="ai-chat-header">
                        <div class="ai-chat-header-info">
                            <div class="ai-chat-avatar">✨</div>
                            <div class="ai-chat-title">
                                <div class="ai-chat-name">Tư Vấn Áo Dài AI</div>
                                <div class="ai-chat-status">
                                    <span class="ai-status-dot"></span>
                                    Online
                                </div>
                            </div>
                        </div>
                        <div class="ai-chat-controls">
                            <button class="ai-chat-btn" id="aiClearBtn" title="Xóa lịch sử">
                                🗑️
                            </button>
                            <button class="ai-chat-btn" id="aiMaximizeBtn" title="Phóng to" style="display: none;">
                                ⛶
                            </button>
                            <button class="ai-chat-btn ai-chat-btn-close" id="aiCloseBtn" title="Đóng">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="ai-messages-container" id="aiMessagesContainer">
                        <!-- Messages will be inserted here -->
                    </div>

                    <!-- Input Area -->
                    <div class="ai-input-container">
                        <div class="ai-input-wrapper">
                            <input 
                                type="text" 
                                class="ai-input-field" 
                                id="aiInputField" 
                                placeholder="Nhắn tin cho tôi..."
                                autocomplete="off"
                            />
                            <button class="ai-send-btn" id="aiSendBtn" title="Gửi">
                                ✈️
                            </button>
                        </div>
                        <div class="ai-footer-text">Powered by AI</div>
                    </div>
                </div>
            </div>
        `;

        // Inject CSS
        const linkCSS = document.createElement('link');
        linkCSS.rel = 'stylesheet';
        linkCSS.href = '/ai_widget/chat.css';
        document.head.appendChild(linkCSS);

        // Inject HTML
        const widgetContainer = document.createElement('div');
        widgetContainer.innerHTML = widgetHTML;
        document.body.appendChild(widgetContainer);
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const triggerBtn = document.getElementById('aiWidgetTrigger');
        const closeBtn = document.getElementById('aiCloseBtn');
        const clearBtn = document.getElementById('aiClearBtn');
        const maximizeBtn = document.getElementById('aiMaximizeBtn');
        const sendBtn = document.getElementById('aiSendBtn');
        const inputField = document.getElementById('aiInputField');
        const container = document.getElementById('aiWidgetContainer');

        // Toggle chat
        triggerBtn.addEventListener('click', () => this.toggleChat());

        // Close chat
        closeBtn.addEventListener('click', () => this.closeChat());

        // Maximize
        maximizeBtn.addEventListener('click', () => this.toggleMaximize());

        // Clear history
        clearBtn.addEventListener('click', () => this.clearHistory());

        // Send message
        sendBtn.addEventListener('click', () => this.sendMessage());
        inputField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Update input value
        inputField.addEventListener('input', (e) => {
            this.input = e.target.value;
            this.updateSendButtonState();
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target) && !triggerBtn.contains(e.target)) {
                if (this.isOpen && !this.isExpanded) {
                    // Don't close, keep open for conversation
                }
            }
        });
    }

    /**
     * Setup auto-scroll to newest message
     */
    setupAutoScroll() {
        const observer = new MutationObserver(() => {
            const messagesContainer = document.getElementById('aiMessagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });

        // Start observing after a brief delay
        setTimeout(() => {
            const messagesContainer = document.getElementById('aiMessagesContainer');
            if (messagesContainer) {
                observer.observe(messagesContainer, { childList: true, subtree: true });
            }
        }, 100);
    }

    /**
     * Toggle chat window
     */
    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    /**
     * Open chat
     */
    openChat() {
        this.isOpen = true;
        const container = document.getElementById('aiWidgetContainer');
        container.classList.add('open');
        
        const inputField = document.getElementById('aiInputField');
        setTimeout(() => inputField.focus(), 300);

        // Show message if first time
        if (this.messages.length === 0) {
            this.addInitialMessage();
        }

        this.renderMessages();
    }

    /**
     * Close chat
     */
    closeChat() {
        this.isOpen = false;
        const container = document.getElementById('aiWidgetContainer');
        container.classList.remove('open');
    }

    /**
     * Toggle maximize
     */
    toggleMaximize() {
        this.isExpanded = !this.isExpanded;
        const chatWindow = document.getElementById('aiChatWindow');
        const maximizeBtn = document.getElementById('aiMaximizeBtn');

        if (this.isExpanded) {
            chatWindow.classList.add('expanded');
            maximizeBtn.textContent = '⬇️';
        } else {
            chatWindow.classList.remove('expanded');
            maximizeBtn.textContent = '⛶';
        }
    }

    /**
     * Clear chat history
     */
    clearHistory() {
        if (confirm('Xóa lịch sử trò chuyện? Hành động này không thể hoàn tác.')) {
            this.messages = [];
            this.currentConversationId = null;
            this.input = '';
            this.saveToStorage();
            this.renderMessages();
            this.addInitialMessage();
        }
    }

    /**
     * Add initial welcome message
     */
    addInitialMessage() {
        const welcomeMsg = {
            id: `msg-${Date.now()}`,
            sender: 'ai',
            text: '🎀 Xin chào! Tôi là trợ lý ảo của QHTN Fashion Rental. Mình có thể giúp bạn tìm áo dài, tư vấn size, kiểm tra đơn hàng hoặc trả lời bất kỳ câu hỏi nào. Bạn muốn hỏi gì?',
            timestamp: new Date().toISOString()
        };
        this.messages.push(welcomeMsg);
        this.saveToStorage();
    }

    /**
     * Send message
     */
    async sendMessage() {
        if (!this.input.trim() || this.isTyping) return;

        // Add user message
        const userMsg = {
            id: `msg-${Date.now()}`,
            sender: 'user',
            text: this.input.trim(),
            timestamp: new Date().toISOString()
        };

        this.messages.push(userMsg);
        this.input = '';
        document.getElementById('aiInputField').value = '';
        this.updateSendButtonState();
        this.renderMessages();
        this.saveToStorage();

        // Show typing indicator
        this.isTyping = true;
        this.renderMessages();

        try {
            // Call backend API (Phase 3)
            const response = await fetch('/AI/chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: userMsg.text,
                    conversationId: this.currentConversationId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentConversationId = data.conversationId;

                // Add AI response
                const aiMsg = {
                    id: data.aiMessage.id,
                    sender: 'ai',
                    text: data.aiMessage.text,
                    timestamp: data.aiMessage.timestamp
                };

                this.messages.push(aiMsg);
                this.saveToStorage();
            } else {
                throw new Error(data.message || 'Failed to get response');
            }
        } catch (error) {
            console.error('Chat error:', error);

            // Add error message
            const errorMsg = {
                id: `msg-${Date.now()}`,
                sender: 'ai',
                text: '😞 Xin lỗi, mình gặp sự cố kết nối. Vui lòng thử lại sau.',
                timestamp: new Date().toISOString(),
                isError: true
            };

            this.messages.push(errorMsg);
        } finally {
            this.isTyping = false;
            this.renderMessages();
            this.saveToStorage();
        }
    }

    /**
     * Render all messages
     */
    renderMessages() {
        const container = document.getElementById('aiMessagesContainer');
        if (!container) return;

        container.innerHTML = '';

        // Render messages
        this.messages.forEach((msg) => {
            const msgEl = this.createMessageElement(msg);
            container.appendChild(msgEl);
        });

        // Render typing indicator if needed
        if (this.isTyping) {
            const typingEl = document.createElement('div');
            typingEl.className = 'ai-message-wrapper ai';
            typingEl.innerHTML = `
                <div class="ai-message-avatar">✨</div>
                <div class="ai-typing-indicator">
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                </div>
            `;
            container.appendChild(typingEl);
        }

        // Auto scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Create message element
     */
    createMessageElement(msg) {
        const wrapper = document.createElement('div');
        wrapper.className = `ai-message-wrapper ${msg.sender}`;

        let html = '';
        
        if (msg.sender === 'ai') {
            html = `
                <div class="ai-message-avatar">✨</div>
                <div class="ai-message ai ${msg.isError ? 'error' : ''}">
                    ${this.escapeHtml(msg.text)}
                </div>
            `;
        } else {
            html = `
                <div class="ai-message user">
                    ${this.escapeHtml(msg.text)}
                </div>
            `;
        }

        wrapper.innerHTML = html;
        return wrapper;
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Update send button state
     */
    updateSendButtonState() {
        const sendBtn = document.getElementById('aiSendBtn');
        if (sendBtn) {
            sendBtn.disabled = !this.input.trim() || this.isTyping;
        }
    }

    /**
     * Load from localStorage
     */
    loadFromStorage() {
        try {
            const stored = localStorage.getItem('qhtn_ai_chat_messages');
            if (stored) {
                this.messages = JSON.parse(stored);
            }

            const conversationId = localStorage.getItem('qhtn_conversation_id');
            if (conversationId) {
                this.currentConversationId = conversationId;
            }
        } catch (error) {
            console.error('Error loading from storage:', error);
            this.messages = [];
        }
    }

    /**
     * Save to localStorage
     */
    saveToStorage() {
        try {
            localStorage.setItem('qhtn_ai_chat_messages', JSON.stringify(this.messages));
            if (this.currentConversationId) {
                localStorage.setItem('qhtn_conversation_id', this.currentConversationId);
            }
        } catch (error) {
            console.error('Error saving to storage:', error);
        }
    }

    /**
     * Add message (for external use)
     */
    addMessage(text, sender = 'user') {
        const msg = {
            id: `msg-${Date.now()}`,
            sender,
            text,
            timestamp: new Date().toISOString()
        };

        this.messages.push(msg);
        this.saveToStorage();
        this.renderMessages();
    }
}

// Initialize widget when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AIChartWidget();
    });
} else {
    new AIChartWidget();
}

/**
 * AI Chat Widget - QHTN Fashion Rental
 * Pink Luxury Theme
 * Version 2.0 - Phase 4: Product Integration
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
        
        // Store globally for quick reply access
        window.aiChatWidget = this;
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
                <button class="ai-widget-button" id="aiWidgetTrigger" title="Trợ giúp">
                    <img src="img/chatbot/customer-service.png" alt="Chat" />
                </button>
            </div>

            <!-- Chat Window -->
            <div class="ai-widget-container" id="aiWidgetContainer">
                <div class="ai-chat-window" id="aiChatWindow">
                    <!-- Header -->
                    <div class="ai-chat-header">
                        <div class="ai-chat-header-info">
                            <img src="img/chatbot/customer-service.png" class="ai-chat-avatar" alt="Atelier">
                            <div class="ai-chat-title">
                                <div class="ai-chat-name">Atelier</div>
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
                            <button class="ai-chat-btn" id="aiMaximizeBtn" title="Phóng to">
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
                </div>
            </div>
        `;

        // Inject CSS
        const linkCSS = document.createElement('link');
        linkCSS.rel = 'stylesheet';
        // Use relative path to support subdirectories and add timestamp to bust cache
        linkCSS.href = 'ai_widget/chat.css?v=' + new Date().getTime();
        document.head.appendChild(linkCSS);

        // Create container and append HTML
        const container = document.createElement('div');
        container.innerHTML = widgetHTML;
        document.body.appendChild(container.firstElementChild);
        document.body.appendChild(container.lastElementChild);

        // Inject inline CSS for any missing styles
        const styleEl = document.createElement('style');
        styleEl.textContent = `
            body { position: relative; }
        `;
        document.head.appendChild(styleEl);

        this.renderInitialMessages();
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Floating button
        const trigger = document.getElementById('aiWidgetTrigger');
        if (trigger) trigger.addEventListener('click', () => this.toggleChat());

        // Close button
        const closeBtn = document.getElementById('aiCloseBtn');
        if (closeBtn) closeBtn.addEventListener('click', () => this.closeChat());

        // Clear button
        const clearBtn = document.getElementById('aiClearBtn');
        if (clearBtn) clearBtn.addEventListener('click', () => this.clearHistory());

        // Maximize button
        const maximizeBtn = document.getElementById('aiMaximizeBtn');
        if (maximizeBtn) maximizeBtn.addEventListener('click', () => this.toggleMaximize());

        // Input field
        const inputField = document.getElementById('aiInputField');
        if (inputField) {
            inputField.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.sendMessage();
            });
            inputField.addEventListener('input', (e) => {
                this.input = e.target.value;
                this.updateSendButtonState();
            });
        }

        // Send button
        const sendBtn = document.getElementById('aiSendBtn');
        if (sendBtn) sendBtn.addEventListener('click', () => this.sendMessage());
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
        if (container) {
            container.style.display = 'flex';
            setTimeout(() => {
                container.classList.add('ai-widget-open');
                const input = document.getElementById('aiInputField');
                if (input) input.focus();
            }, 10);
        }
    }

    /**
     * Close chat
     */
    closeChat() {
        this.isOpen = false;
        const container = document.getElementById('aiWidgetContainer');
        if (container) {
            container.classList.remove('ai-widget-open');
            setTimeout(() => {
                container.style.display = 'none';
            }, 300);
        }
    }

    toggleMaximize() {
        this.isExpanded = !this.isExpanded;
        const container = document.getElementById('aiWidgetContainer');
        if (container) {
            if (this.isExpanded) {
                container.classList.add('ai-maximized');
            } else {
                container.classList.remove('ai-maximized');
            }
        }
    }

    /**
     * Clear chat history
     */
    clearHistory() {
        if (confirm('Bạn chắc chắn muốn xóa lịch sử trò chuyện?')) {
            this.messages = [];
            this.currentConversationId = null;
            this.saveToStorage();
            this.renderInitialMessages();
        }
    }

    /**
     * Render initial welcome message - Phase 5: Enhanced greeting
     */
    renderInitialMessages() {
        if (this.messages.length === 0) {
            const welcomeMsg = {
                id: `msg-${Date.now()}`,
                sender: 'ai',
                text: 'Xin chào, chúc Quý khách một ngày tốt lành! ❤ Đây là Atelier, trợ lý ảo của QHTN Fashion Rental. Quý khách muốn Atelier hỗ trợ về vấn đề gì ạ?',
                timestamp: new Date().toISOString()
            };
            this.messages.push(welcomeMsg);
            this.saveToStorage();
        }
        this.renderMessages();
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
            // Call backend API (Phase 4)
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

                // Add AI response with products and quick replies
                const aiMsg = {
                    id: data.aiMessage.id,
                    sender: 'ai',
                    text: data.aiMessage.text,
                    products: data.aiMessage.products || [],
                    quickReplies: data.aiMessage.quickReplies || [],
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
     * Handle quick reply click
     */
    userSelectQuickReply(text) {
        document.getElementById('aiInputField').value = text;
        this.input = text;
        this.sendMessage();
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
                <img src="img/chatbot/customer-service.png" class="ai-message-avatar" alt="AI">
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

        // Phase 5: Load lazy images
        this.observeLazyImages();
    }

    /**
     * Create message element - Phase 4: Enhanced with products & quick replies
     */
    createMessageElement(msg) {
        const wrapper = document.createElement('div');
        wrapper.className = `ai-message-wrapper ${msg.sender}`;

        let html = '';
        
        if (msg.sender === 'ai') {
            html = `
                <img src="img/chatbot/customer-service.png" class="ai-message-avatar" alt="AI">
                <div class="ai-message-content ai ${msg.isError ? 'error' : ''}">
                    <div class="ai-message ai ${msg.isError ? 'error' : ''}">
                        ${this.escapeHtml(msg.text)}
                    </div>`;

            // Add product cards if exist - Phase 5: Lazy loading with blur-up
            if (msg.products && msg.products.length > 0) {
                html += `<div class="ai-products-container">`;
                msg.products.forEach(product => {
                    html += `
                        <a href="${this.escapeHtml(product.url)}" class="ai-product-card" target="_blank" title="${this.escapeHtml(product.name)}">
                            <div class="ai-product-img">
                                <img 
                                    class="ai-product-img-lazy" 
                                    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='1 1 1 1'%3E%3C/svg%3E" 
                                    data-src="${this.escapeHtml(product.image)}" 
                                    alt="${this.escapeHtml(product.name)}" 
                                    loading="lazy"
                                />
                                <span class="ai-product-badge">Cho thuê</span>
                            </div>
                            <div class="ai-product-info">
                                <div class="ai-product-name">${this.escapeHtml(product.name)}</div>
                                <div class="ai-product-price">${this.formatPrice(product.price)}/ngày</div>
                            </div>
                        </a>
                    `;
                });
                html += `</div>`;
            }

            // Add quick reply buttons if exist
            if (msg.quickReplies && msg.quickReplies.length > 0) {
                html += `<div class="ai-quick-replies">`;
                msg.quickReplies.forEach((reply, index) => {
                    html += `
                        <button 
                            class="ai-quick-reply-btn" 
                            onclick="window.aiChatWidget.userSelectQuickReply('${this.escapeJs(reply.value)}')"
                            type="button"
                        >
                            ${this.escapeHtml(reply.text)}
                        </button>
                    `;
                });
                html += `</div>`;
            }

            // Feedback buttons removed per new clean design
            
            html += `</div>`;
        } else {
            html = `
                <div class="ai-message-content user">
                    <div class="ai-message user">
                        ${this.escapeHtml(msg.text)}
                    </div>
                </div>
                <img src="img/chatbot/user.png" class="ai-message-avatar" alt="User">
            `;
        }

        wrapper.innerHTML = html;
        return wrapper;
    }

    /**
     * Format price in Vietnamese format
     */
    formatPrice(price) {
        if (!price) return '0';
        return Math.round(price).toLocaleString('vi-VN');
    }

    /**
     * Escape HTML special characters
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Escape JavaScript special characters
     */
    escapeJs(text) {
        return text
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/"/g, '\\"')
            .replace(/\n/g, '\\n')
            .replace(/\r/g, '\\r');
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
     * Setup auto scroll
     */
    setupAutoScroll() {
        // Auto scroll will be handled in renderMessages()
    }

    /**
     * Phase 5: Submit feedback on AI message
     */
    submitFeedback(messageId, rating) {
        try {
            // Add feedback to message
            const msgIndex = this.messages.findIndex(m => m.id === messageId);
            if (msgIndex !== -1) {
                this.messages[msgIndex].feedback = rating;
                this.saveToStorage();
            }

            // Update UI - highlight selected feedback button
            const feedbackContainer = document.querySelector(`[data-message-id="${messageId}"]`);
            if (feedbackContainer) {
                feedbackContainer.classList.add('ai-feedback-submitted');
                const buttons = feedbackContainer.querySelectorAll('.ai-feedback-btn');
                buttons.forEach(btn => {
                    const btnRating = btn.textContent === '👍' ? 1 : -1;
                    if (btnRating === rating) {
                        btn.classList.add('ai-feedback-active');
                        btn.style.opacity = '1';
                    } else {
                        btn.style.opacity = '0.4';
                    }
                });
            }

            // Log feedback (for analytics in Phase 6)
            console.log(`📊 User feedback: Message ${messageId} → ${rating > 0 ? 'Helpful' : 'Not helpful'}`);

        } catch (error) {
            console.error('Feedback error:', error);
        }
    }

    /**
     * Phase 5: Load lazy images when they come into view
     */
    observeLazyImages() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;
                        
                        if (src) {
                            img.src = src;
                            img.classList.add('ai-product-img-loaded');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px'
            });

            // Observe all lazy images
            document.querySelectorAll('.ai-product-img-lazy').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('.ai-product-img-lazy').forEach(img => {
                const src = img.dataset.src;
                if (src) {
                    img.src = src;
                    img.classList.add('ai-product-img-loaded');
                }
            });
        }
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.aiChatWidget) {
            new AIChartWidget();
        }
    });
} else {
    if (!window.aiChatWidget) {
        new AIChartWidget();
    }
}

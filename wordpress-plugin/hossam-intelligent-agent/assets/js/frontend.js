// Frontend JavaScript for Hossam Intelligent Agent

jQuery(document).ready(function($) {
    
    // Initialize chat widget
    initializeChatWidget();
    
    function initializeChatWidget() {
        const chatWidget = $('.hia-chat-widget');
        if (chatWidget.length === 0) return;
        
        const messagesContainer = $('#hia-chat-messages');
        const messageInput = $('#hia-message-input');
        const sendButton = $('#hia-send-button');
        
        // Add initial welcome message
        addMessage('assistant', 'مرحبا! كيف يمكنني مساعدتك اليوم؟');
        
        // Send message on button click
        sendButton.on('click', function() {
            sendMessage();
        });
        
        // Send message on Enter key press
        messageInput.on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage();
            }
        });
        
        function sendMessage() {
            const message = messageInput.val().trim();
            if (message === '') return;
            
            // Disable input while sending
            messageInput.prop('disabled', true);
            sendButton.prop('disabled', true);
            
            // Add user message to chat
            addMessage('user', message);
            
            // Clear input
            messageInput.val('');
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send AJAX request
            $.ajax({
                url: hia_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hia_send_message',
                    message: message,
                    nonce: hia_ajax.nonce
                },
                success: function(response) {
                    hideTypingIndicator();
                    
                    if (response.success) {
                        addMessage('assistant', response.message);
                    } else {
                        addMessage('error', response.message || 'حدث خطأ في الإرسال');
                    }
                },
                error: function(xhr, status, error) {
                    hideTypingIndicator();
                    addMessage('error', 'خطأ في الاتصال: ' + error);
                },
                complete: function() {
                    // Re-enable input
                    messageInput.prop('disabled', false);
                    sendButton.prop('disabled', false);
                    messageInput.focus();
                }
            });
        }
        
        function addMessage(type, text) {
            const messageDiv = $('<div>').addClass('hia-message').addClass(type);
            messageDiv.text(text);
            messagesContainer.append(messageDiv);
            
            // Scroll to bottom
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }
        
        function showTypingIndicator() {
            const typingDiv = $('<div>').addClass('hia-typing-indicator active');
            typingDiv.text('يكتب...');
            messagesContainer.append(typingDiv);
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }
        
        function hideTypingIndicator() {
            messagesContainer.find('.hia-typing-indicator').remove();
        }
    }
    
    // Auto-resize chat widget based on content
    function autoResize() {
        $('.hia-chat-widget').each(function() {
            const widget = $(this);
            const messages = widget.find('.hia-chat-messages');
            
            // Ensure minimum height
            if (messages.height() < 200) {
                messages.css('min-height', '200px');
            }
        });
    }
    
    // Call auto-resize on window resize
    $(window).on('resize', autoResize);
    autoResize();
    
    // Add smooth animations
    $('.hia-message').hide().fadeIn(300);
    
});
// Chat functionality
function toggleChat() {
    const chatPopup = document.getElementById('chat-popup');
    const isHidden = chatPopup.style.display === 'none' || chatPopup.style.display === '';
    chatPopup.style.display = isHidden ? 'block' : 'none';
    
    // Show welcome message when chat is opened
    if (isHidden) {
        const chatbox = document.getElementById('chatbox');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=help'
        })
        .then(async res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            if (data && data.reply) {
                chatbox.innerHTML = `
                    <div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;">
                        <strong>Steve:</strong> ${data.reply.replace(/\n/g, '<br>')}
                    </div>
                `;
            }
            chatbox.scrollTop = chatbox.scrollHeight;
        })
        .catch(error => {
            console.error('Error:', error);
            chatbox.innerHTML = `<div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;"><strong>Steve:</strong> How can I help you today?</div>`;
        });
    }
}

function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value;
    if (!message.trim()) return;

    const chatbox = document.getElementById('chatbox');
    input.value = '';

    fetch(window.location.href, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message=' + encodeURIComponent(message)
    })
    .then(async res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Invalid JSON response');
        }
    })
    .then(data => {
        console.log('Server response:', data);
        if (data && data.reply) {
            let formattedReply = data.reply
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            chatbox.innerHTML += `
                <div class="user-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #f0f0f0;">
                    <strong>You:</strong> ${message}
                </div>
                <div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;">
                    <strong>Steve:</strong> ${formattedReply}
                </div>
            `;
        } else {
            throw new Error('Invalid response format');
        }
        chatbox.scrollTop = chatbox.scrollHeight;
    })
    .catch(error => {
        console.error('Error:', error);
        chatbox.innerHTML += `<div class="bot"><strong>Steve:</strong> Sorry, there was an error processing your request. (${error.message})</div>`;
        chatbox.scrollTop = chatbox.scrollHeight;
    });
}

// Add event listeners when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('userInput');
    
    // Add keypress event listener for Enter key
    input.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });
}); 
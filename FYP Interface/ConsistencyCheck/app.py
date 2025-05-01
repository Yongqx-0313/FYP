import ollama
import gradio as gr
import re

def chat_with_ollama(message, history):
    # Initialize empty string for streaming response
    response = ""
    
    # Convert history to messages format
    messages = [
        {"role": "system", "content": "You are a helpful assistant.Respond concisely, avoid long thinking steps, and keep replies under 1-2 lines. Be confident and avoid unnecessary elaboration."}
    ]
    
    # Add history messages
    for h in history:
        messages.append({"role": "user", "content": h[0]})
        if h[1]:  # Only add assistant message if it exists
            messages.append({"role": "assistant", "content": h[1]})
    
    # Add current message
    messages.append({"role": "user", "content": message})
    
    completion = ollama.chat(
        model="deepseek-r1:latest",
        messages=messages,
        stream=True,  # Enable streaming
        options={"temperature": 1.0}
    )
    
    # Stream the response
    for chunk in completion:
        if 'message' in chunk and 'content' in chunk['message']:
            content = chunk['message']['content']
            # Handle <think> and </think> tags
            # content = content.replace("<think>", "Thinking...").replace("</think>", "\n\n Answer:")
            response = re.sub(r'<think>.*?</think>', '', response, flags=re.DOTALL)
            response += content
            yield response
    
    # Collect full response before returning
    # for chunk in completion:
    #     if 'message' in chunk and 'content' in chunk['message']:
    #         response += chunk['message']['content']

    # # Remove any <think> tags and return final cleaned answer
    # response = re.sub(r'<think>.*?</think>', '', response, flags=re.DOTALL)
    # yield response
css = """
.clear-button {
    font-size: 20px !important;
    padding: 10px 20px;
    background-color: #e71488 !important;
    color: white !important;
    border-radius: 8px;
    font-weight: bold;
}

.my-textbox textarea {
    background-color: white !important;
    color: black;
}

"""
# Create Gradio interface with Chatbot
with gr.Blocks(css=css) as demo:
    chatbot = gr.Chatbot()
    msg = gr.Textbox(placeholder="Enter your message here...", elem_classes="my-textbox")
    clear = gr.Button("Clear", elem_classes="clear-button")

    def user(user_message, history):
        return "", history + [[user_message, None]]

    def bot(history):
        history[-1][1] = ""
        for chunk in chat_with_ollama(history[-1][0], history[:-1]):
            history[-1][1] = chunk
            yield history

    msg.submit(user, [msg, chatbot], [msg, chatbot], queue=False).then(
        bot, chatbot, chatbot
    )
    clear.click(lambda: None, None, chatbot, queue=False)

if __name__ == "__main__":
    demo.launch()
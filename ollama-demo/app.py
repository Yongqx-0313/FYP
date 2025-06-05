import os
import ollama
import gradio as gr
import re
import urllib.parse
import requests

# ----------- FAQ Handling -------------
def check_system_faq(message):
    faqs = {
        "what does a correct use case diagram look like?": {
            "type": "mixed",
            "text": "Below is an example of a correctly structured use case diagram based on UML standards.",
            "image": "./static/images/Standard_UCD.png"
        },
        "how are include and extend relationships checked?": {
            "type": "text",
            "text": "The system compares include/extend relationships found in your diagram with those mentioned in your document text. If the counts don’t match, it alerts you to review your design or text."
        },
        "what does floating use case mean?": {
            "type": "mixed",
            "text": "A 'floating use case' is a use case that is not connected to any actor or system. This often indicates a missing line.",
        },
        "what is association relationship?": {
            "type": "mixed",
            "text": "The Association Relationship shows a connection or interaction between an actor and a use case. It is drawn as a line linking the actor to the use case. Below is an example image.",
            "image": "./static/images/Association.png"
        },
        "what is include relationship?": {
            "type": "mixed",
            "text": "The Include Relationship means one use case always uses the actions of another. It's shown as a dashed arrow from the main use case to the one it includes.",
            "image": "./static/images/Include.png"
        },
        "what is extends relationship?": {
            "type": "mixed",
            "text": "The Extend Relationship adds optional steps only when certain conditions happen. Shown as a dashed arrow labeled 'extend'.",
            "image": "./static/images/Extends.png"
        },
        "what is generalization relationship?": {
            "type": "mixed",
            "text": "Generalization means one use case is a more specific version of another. Shown with an arrow from the detailed to the general use case.",
            "image": "./static/images/Generalize.png"
        },
        "what is actor and how it look likes?": {
            "type": "mixed",
            "text": "Actors are users or systems outside the main system that interact with it. They are drawn as stick figures. Example image below.",
            "image": "./static/images/Actor.png"
        },
        "what is use case and how it look likes?": {
            "type": "mixed",
            "text": "Use cases are system tasks drawn as ovals. For example, 'Place Order' or 'Track Delivery'.",
            "image": "./static/images/Use_Case.png"
        },
        "what is system boundary and how it look likes?": {
            "type": "mixed",
            "text": "System boundary shows the scope of your system using a rectangle around use cases.",
            "image": "./static/images/System_boundary.png"
        },
        "where should the system name be placed?": {
            "type": "text",
            "text": "The “System Name” should be placed at the top center **inside** the system boundary."
        }
    }

    normalized_message = message.strip().lower()
    for key, reply in faqs.items():
        if key.strip().lower() in normalized_message:
            return reply
    return None

def get_faq_list():
    return [
        "🧠 Here are some questions you can ask me:",
        "• What does a correct use case diagram look like?",
        "• How are include and extend relationships checked?",
        "• What does floating use case mean?",
        "• What is association relationship?",
        "• What is include relationship?",
        "• What is extends relationship?",
        "• What is generalization relationship?",
        "• What is actor and how it look likes?",
        "• What is use case and how it look likes?",
        "• What is system boundary and how it look likes?",
        "• Where should the system name be placed?"
    ]

# ----------- AI Chat + PlantUML ---------
def chat_with_ollama(message, history):
    response = ""
    try:
        messages = [{
            "role": "system",
            "content": (
                "You are a helpful assistant. Respond concisely and return valid PlantUML code for use case diagrams.\n\n"
                "- Generate valid PlantUML for use case diagrams\n"
                "- Help users write and fix PlantUML syntax\n"
                "- Explain UML elements like actors and use cases\n\n"
                "Only respond with UML help. Do NOT answer questions about the UI or system buttons.\n\n"
                "Always follow this format:\n"
                "@startuml\n"
                "left to right direction\n"
                "actor \"Food Critic\" as fc\n"
                "rectangle Restaurant_Management_System {\n"
                "  usecase \"Eat Food\" as UC1\n"
                "  usecase \"Pay for Food\" as UC2\n"
                "  usecase \"Drink\" as UC3\n"
                "}\n"
                "fc --> UC1\n"
                "fc --> UC2\n"
                "fc --> UC3\n"
                "@enduml\n\n"
                "⚠️ RULES:\n"
                "- Use this syntax for actors: `actor \"Actor Name\" as A`\n"
                "- Use this syntax for system: `rectangle System_Name {}` — **DO NOT USE SPACES** in System_Name (e.g. `Hotel_Management_System`, not `Hotel Management System`)\n"
                "- NEVER wrap actors or systems with `{}` unless it's inside `rectangle System_Name {}`\n"
                "- Use aliases in arrows like `A --> UC1`\n"
                "- Usecase names with spaces MUST be inside double quotes: `usecase \"Submit Assignment\" as UC1`\n"
            )
        }]
        for h in history:
            messages.append({"role": "user", "content": h[0]})
            if h[1]:
                messages.append({"role": "assistant", "content": h[1]})

        messages.append({"role": "user", "content": message})

        completion = ollama.chat(
            model="deepseek-r1:latest",
            messages=messages,
            stream=True,
            options={"temperature": 1.0}
        )

        for chunk in completion:
            if 'message' in chunk and 'content' in chunk['message']:
                content = chunk['message']['content']
                content = content.replace("<think>", "Thinking...").replace("</think>", "\n\nAnswer:")
                response += content
                yield response
    except Exception as e:
        print("Error during chat:", str(e))
        yield response + "\n\n⚠️ An error occurred. The response was cut off."

def extract_plantuml(text):
    match = re.search(r'@startuml.*?@enduml', text, re.DOTALL)
    return match.group(0) if match else None

def generate_plantuml_url(uml_code):
    encoded_text = urllib.parse.quote(uml_code)
    return f"https://plantumlgen.pythonanywhere.com/generate_uml?uml_text={encoded_text}"

# ----------- Gradio UI ------------------
with gr.Blocks(css="""body { font-family: sans-serif; }""") as demo:
    gr.Markdown("# 💬 Deepseek Use Case Diagram Chatbot\nAsk about UML diagrams or request a PlantUML example!")
    gr.Markdown("""📘 **Note:** This chatbot uses UML standards defined by the [Object Management Group (OMG)](https://www.uml-diagrams.org/) and visual examples sourced from educational resources such as [GeeksforGeeks](https://www.geeksforgeeks.org/).""")

    chatbot = gr.Chatbot()
    msg = gr.Textbox(placeholder="Enter your question or prompt...", label="Your Prompt")
    clear = gr.Button("Clear")
    uml_image = gr.Image(label="Diagram Preview", visible=False)
    first_run = gr.State(value=True)

    def user(user_message, history, first):
        return "", history + [[user_message, None]], False

    def greet_on_load():
        intro_message = (
            "👋 Hello! I'm your AI UML Assistant.\n\n"
            "You can ask me:\n"
            "- To show a correct use case diagram\n"
            "- What does 'include' or 'extend' mean?\n"
            "- To generate PlantUML code for your system\n"
            "- To explain UML elements like actor, system boundary, etc.\n\n"
            "Type `/help` to see all questions I can answer."
            "📘 **Note:** This chatbot follows UML standards from [OMG](https://www.uml-diagrams.org/) and uses examples from sources like GeeksforGeeks."
        )
        return [[ "", intro_message ]], None, False

    def bot(history):
        history[-1][1] = ""
        user_message = history[-1][0]

        # /help command
        if user_message.strip().lower() == "/help":
            history[-1][1] = "\n".join(get_faq_list())
            yield history, gr.update(value=None, visible=False)
            return

        faq_answer = check_system_faq(user_message)
        if faq_answer:
            answer_type = faq_answer["type"]
            if answer_type == "text":
                history[-1][1] = faq_answer["text"]
                yield history, gr.update(value=None, visible=False)
            elif answer_type == "image":
                history[-1][1] = "Here’s the image you requested:"
                yield history, gr.update(value=faq_answer["image"], visible=True)
            elif answer_type == "mixed":
                history[-1][1] = faq_answer["text"]
                yield history, gr.update(value=faq_answer.get("image"), visible=True)
            return

        for chunk in chat_with_ollama(user_message, history[:-1]):
            history[-1][1] = chunk
            uml_code = extract_plantuml(chunk)
            if uml_code and "@startuml" in uml_code and "@enduml" in uml_code:
                if len(uml_code) > 5000:
                    yield history, gr.update(value=None, visible=False)
                else:
                    uml_url = generate_plantuml_url(uml_code)
                    try:
                        response = requests.get(uml_url)
                        if response.status_code == 200:
                            yield history, gr.update(value=uml_url, visible=True, format='url')
                        else:
                            yield history, gr.update(value=None, visible=False)
                    except:
                        yield history, gr.update(value=None, visible=False)
            else:
                yield history, gr.update(value=None, visible=False)

    # Submit message
    msg.submit(user, [msg, chatbot, first_run], [msg, chatbot, first_run], queue=False).then(
        bot, [chatbot], [chatbot, uml_image]
    )

    # Clear
    clear.click(lambda: ([], None), None, [chatbot, uml_image], queue=False)

    # Trigger greeting on load
    demo.load(fn=greet_on_load, inputs=None, outputs=[chatbot, uml_image, first_run])

# ----------- Launch App -----------------
if __name__ == "__main__":
    demo.launch(show_api=False, share=False)

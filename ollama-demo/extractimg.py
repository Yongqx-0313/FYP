# pip install inference-sdk
# import the inference-sdk
from inference_sdk import InferenceHTTPClient

# initialize the client
CLIENT = InferenceHTTPClient(
    api_url="https://serverless.roboflow.com",
    api_key="erhUiKryC5pLrlT5t0as"
)

# infer on a local image
result = CLIENT.infer("YY_UCD.jpg", model_id="use-case-diagram-ocaut/5")


from importlib.metadata import PackageNotFoundError, version
from pkgutil import extend_path

# Load the rest of the installed AgentKit package while overriding only its
# Codex transport client with the application-maintained compatibility patch.
__path__ = extend_path(__path__, __name__)

try:
    __version__ = version("gpt-image-2-5-agent-kit")
except PackageNotFoundError:
    __version__ = "0.3.1"

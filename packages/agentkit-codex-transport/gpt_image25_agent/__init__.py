from importlib.metadata import PackageNotFoundError, version
from pkgutil import extend_path

# Extend the installed upstream package path without overriding its modules.
# AgentKit's client, payload, streaming and image validation stay upstream-owned.
__path__ = extend_path(__path__, __name__)

try:
    __version__ = version("gpt-image-2-5-agent-kit")
except PackageNotFoundError:
    __version__ = "0.3.1"

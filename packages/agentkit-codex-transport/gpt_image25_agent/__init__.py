from pkgutil import extend_path

# Load the rest of the installed AgentKit package while overriding only its
# Codex transport client with the application-maintained compatibility patch.
__path__ = extend_path(__path__, __name__)

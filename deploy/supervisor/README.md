# AgentKit Worker — AWS Ubuntu

The application uses a dedicated Laravel queue named `agentkit`. On Ubuntu, the recommended production runtime is Supervisor with three independent Laravel queue processes.

## Runtime

- Queue: `agentkit`
- Supervisor program: `rizky-moto-ai-agent`
- Processes: `agent-00`, `agent-01`, `agent-02`
- Laravel user: `www-data`
- Python: a dedicated virtual environment
- Agent Kit CLI: installed into that virtual environment
- Token: loaded from the encrypted database credential pool; never put the token into Supervisor config.

Supervisor is designed to start, stop, restart and monitor multiple child processes and can run them under a dedicated Unix user. citeturn1search0turn1search1

## Install Python + Supervisor

On Ubuntu 22.04/24.04:

```bash
sudo apt update
sudo apt install -y python3-full python3-pip supervisor
```

Ubuntu recommends `python3-full` for the complete Python runtime and virtual-environment support, and `python3-pip` for pip. Ubuntu ships Supervisor as a package. citeturn2search11turn2search10

## Install Agent Kit

From the Laravel project directory:

```bash
cd /var/www/rizky-moto-ai

sudo -u www-data python3 -m venv agent-ai/.venv
sudo -u www-data agent-ai/.venv/bin/python -m pip install --upgrade pip
sudo -u www-data agent-ai/.venv/bin/python -m pip install -e /var/www/gpt-image-2-5-agent-kit
sudo -u www-data agent-ai/.venv/bin/python -m gpt_image25_agent --help
```

The Agent Kit README documents Python 3.10+ and the `gpt-image25-agent` CLI / `gpt_image25_agent` module. Live mode uses the experimental ChatGPT/Codex backend and one live invocation produces one image. citeturn0search0turn0search1

If the Agent Kit repository is elsewhere, replace the editable-install path with that checkout.

## Laravel environment

Add to the production `.env`:

```dotenv
AGENT_AI_PYTHON_BINARY=/var/www/rizky-moto-ai/agent-ai/.venv/bin/python
AGENT_AI_MODULE=gpt_image25_agent
AGENT_AI_QUEUE=agentkit
AGENT_AI_WORKER_COUNT=3
AGENT_AI_WORKER_DRIVER=supervisor
AGENT_AI_WORKER_SUPERVISOR_PROGRAM=rizky-moto-ai-agent
AGENT_AI_WORKER_SUPERVISOR_BIN=/usr/bin/supervisorctl
AGENT_AI_TIMEOUT=300
```

Then:

```bash
php artisan optimize:clear
```

## Install Supervisor program

Copy the example config and adjust the Laravel path if necessary:

```bash
sudo cp deploy/supervisor/rizky-moto-ai-agent.conf.example /etc/supervisor/conf.d/rizky-moto-ai-agent.conf
sudo nano /etc/supervisor/conf.d/rizky-moto-ai-agent.conf
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status rizky-moto-ai-agent:*
```

The expected state is:

```text
rizky-moto-ai-agent:agent-00   RUNNING
rizky-moto-ai-agent:agent-01   RUNNING
rizky-moto-ai-agent:agent-02   RUNNING
```

## Allow the website to control AgentKit workers

Install the restricted sudoers example:

```bash
sudo cp deploy/supervisor/rizky-moto-ai-agent.sudoers.example /etc/sudoers.d/rizky-moto-ai-agent
sudo chmod 0440 /etc/sudoers.d/rizky-moto-ai-agent
sudo visudo -cf /etc/sudoers.d/rizky-moto-ai-agent
```

This only grants the Laravel web user status/start/stop/restart access for the AgentKit Supervisor group.

## Verify from Laravel

```bash
php artisan agent:worker status
php artisan agent:worker restart
php artisan agent:worker status
```

Then open the dashboard and use **AI Engine → AgentKit Workers**.

## Important

Do not put `CHATGPT_CODEX_ACCESS_TOKEN` into Supervisor config. The application obtains the selected encrypted credential from the Agent credential pool and injects it only into the child Agent Kit process for the duration of the invocation.


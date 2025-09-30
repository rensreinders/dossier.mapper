# =========================
# Config
# =========================
VERSION    ?= 0.1.0
ENGINE     ?= podman
DOCKERFILE ?= .docker/Dockerfile
CHART_DIR  ?= .k8s/helm

# Registry + repo
REGISTRY   ?= harbor.k8s.rapide.software
PROJECT    ?= mijnkantoor
IMAGE_NAME ?= import-mapper
IMAGE_REPO  = $(REGISTRY)/$(PROJECT)/$(IMAGE_NAME)

# Deploy/Helm vars
NAMESPACE              ?= mijnkantoor-production-import-mapper
HOSTNAME               ?= import-mapper.mijnkantoorapp.nl
HELM_INSTALLATION_NAME ?= mijnkantoor-production-import-mapper
TLS_SECRET             ?= import-mapper-mijnkantoorapp-nl-tls

# Dev
DEV_TAG   ?= dev
DEV_IMAGE  = $(IMAGE_REPO):$(DEV_TAG)
DEV_PORT  ?= 28080

# Prod
PROD_IMAGE = $(IMAGE_REPO):$(VERSION)

# ConfigMap/Env
CONFIGMAP_NAME ?= import-mapper-env
ENV_FILE       ?= .env.production

# Optioneel: Secret (alleen gebruiken als je secrets wilt scheiden)
SECRET_NAME ?= import-mapper-secret
SECRET_ENV_FILE ?= .env.secrets

# Detect host arch → BUILD_ARCH in {amd64,arm64}
HOST_ARCH := $(shell uname -m)
ifeq ($(HOST_ARCH),x86_64)
  BUILD_ARCH := amd64
else ifeq ($(HOST_ARCH),arm64)
  BUILD_ARCH := arm64
else ifeq ($(HOST_ARCH),aarch64)
  BUILD_ARCH := arm64
else
  BUILD_ARCH := amd64
endif

# Optioneel: BuildKit (voor docker); Podman negeert dit gewoon
export DOCKER_BUILDKIT ?= 1

# =========================
# Targets
# =========================
.PHONY: help dev ensure-dev-image dev-build dev-rebuild prod prod-rebuild push \
        configmap secret deploy print

help:
	@echo "Targets:"
	@echo "  dev            - Run dev container; build alleen als image nog niet bestaat"
	@echo "  dev-build      - Forceer (re)build van dev image"
	@echo "  dev-rebuild    - Rebuild dev image zonder cache"
	@echo "  prod           - Build production image (linux/amd64) met versie $(VERSION)"
	@echo "  prod-rebuild   - Rebuild production image zonder cache"
	@echo "  push           - Push production image naar Harbor"
	@echo "  configmap      - Maak/Update ConfigMap uit $(ENV_FILE)"
	@echo "  secret         - (Optioneel) Maak/Update Secret uit $(SECRET_ENV_FILE)"
	@echo "  deploy         - Helm upgrade/install; zorgt eerst voor ConfigMap (en optioneel Secret)"
	@echo "  print          - Print variabelen"

# --------------------------------------------
# DEV
# --------------------------------------------
dev-build:
	@if [ "$(ENGINE)" = "docker" ]; then \
	  $(ENGINE) build -t $(DEV_IMAGE) -f $(DOCKERFILE) --platform linux/$(BUILD_ARCH) . ; \
	else \
	  $(ENGINE) build -t $(DEV_IMAGE) -f $(DOCKERFILE) --arch $(BUILD_ARCH) --os linux . ; \
	fi

dev: ensure-dev-image
	@echo "Running dev container op http://0.0.0.0:$(DEV_PORT) (intern: 8080)"
	$(ENGINE) run --rm -v "$$PWD/app":/app:Z,rw,U -p $(DEV_PORT):8080 $(DEV_IMAGE)

ensure-dev-image:
	@if ! $(ENGINE) image inspect $(DEV_IMAGE) >/dev/null 2>&1; then \
	  $(MAKE) dev-build; \
	else \
	  echo "✅ Dev image bestaat al: $(DEV_IMAGE)"; \
	fi

dev-rebuild:
	$(ENGINE) build --no-cache -t $(DEV_IMAGE) -f $(DOCKERFILE) .

# --------------------------------------------
# PROD
# --------------------------------------------
prod:
	@if [ "$(ENGINE)" = "docker" ]; then \
	  $(ENGINE) build --pull --platform linux/amd64 -t $(PROD_IMAGE) -f $(DOCKERFILE) . ; \
	else \
	  $(ENGINE) build --pull --arch amd64 --os linux -t $(PROD_IMAGE) -f $(DOCKERFILE) . ; \
	fi
	@echo "Built $(PROD_IMAGE)"

prod-rebuild:
	@if [ "$(ENGINE)" = "docker" ]; then \
	  $(ENGINE) build --no-cache --pull --platform linux/amd64 -t $(PROD_IMAGE) -f $(DOCKERFILE) . ; \
	else \
	  $(ENGINE) build --no-cache --pull --arch amd64 --os linux -t $(PROD_IMAGE) -f $(DOCKERFILE) . ; \
	fi
	@echo "Rebuilt (no cache) $(PROD_IMAGE)"

# --------------------------------------------
# PUSH
# --------------------------------------------
push:
	$(ENGINE) push $(PROD_IMAGE)

# --------------------------------------------
# CONFIG: ConfigMap & Secret
# --------------------------------------------
configmap:
	@if [ ! -f "$(ENV_FILE)" ]; then \
	  echo "❌ $(ENV_FILE) ontbreekt"; exit 1; \
	fi
	kubectl create configmap $(CONFIGMAP_NAME) \
	  --from-env-file=$(ENV_FILE) \
	  -n $(NAMESPACE) \
	  --dry-run=client -o yaml | kubectl apply -f -
	@echo "✅ ConfigMap '$(CONFIGMAP_NAME)' geapply'd in namespace $(NAMESPACE)"

# Optioneel: alleen gebruiken als je secrets via bestand wilt beheren
secret:
	@if [ ! -f "$(SECRET_ENV_FILE)" ]; then \
	  echo "ℹ️ $(SECRET_ENV_FILE) niet gevonden; sla Secret aanmaken over"; \
	else \
	  kubectl create secret generic $(SECRET_NAME) \
	    --from-env-file=$(SECRET_ENV_FILE) \
	    -n $(NAMESPACE) \
	    --dry-run=client -o yaml | kubectl apply -f - ; \
	  echo "✅ Secret '$(SECRET_NAME)' geapply'd in namespace $(NAMESPACE)"; \
	fi

# --------------------------------------------
# DEPLOY (Helm)
# --------------------------------------------
deploy: configmap
	# Voeg 'secret' toe aan de afhankelijkheden als je die gebruikt: `deploy: configmap secret`
	helm upgrade --install $(HELM_INSTALLATION_NAME) $(CHART_DIR) \
		--namespace $(NAMESPACE) --create-namespace \
		--set image.repository=$(IMAGE_REPO) \
		--set image.tag=$(VERSION) \
		--set namespace=$(NAMESPACE) \
		--set hostname=$(HOSTNAME) \
		--set tls.secret=$(TLS_SECRET) \
		--set configMapName=$(CONFIGMAP_NAME) \
		--set secretName=$(SECRET_NAME)

# --------------------------------------------
# Debug
# --------------------------------------------
print:
	@echo "ENGINE        = $(ENGINE)"
	@echo "VERSION       = $(VERSION)"
	@echo "IMAGE_REPO    = $(IMAGE_REPO)"
	@echo "DEV_IMAGE     = $(DEV_IMAGE)"
	@echo "PROD_IMAGE    = $(PROD_IMAGE)"
	@echo "REGISTRY      = $(REGISTRY)"
	@echo "PROJECT       = $(PROJECT)"
	@echo "IMAGE_NAME    = $(IMAGE_NAME)"
	@echo "NAMESPACE     = $(NAMESPACE)"
	@echo "HOSTNAME      = $(HOSTNAME)"
	@echo "HELM_RELEASE  = $(HELM_INSTALLATION_NAME)"
	@echo "TLS_SECRET    = $(TLS_SECRET)"
	@echo "CONFIGMAP     = $(CONFIGMAP_NAME)"
	@echo "ENV_FILE      = $(ENV_FILE)"
	@echo "SECRET_NAME   = $(SECRET_NAME)"
	@echo "SECRET_ENV    = $(SECRET_ENV_FILE)"

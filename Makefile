# =========================
# Config
# =========================
VERSION    ?= 0.1.3
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

# Details dev image
DEV_TAG    ?= dev
DEV_IMAGE   = $(IMAGE_REPO):$(DEV_TAG)

# Details prod image
PROD_IMAGE  = $(IMAGE_REPO):$(VERSION)

# Port to run dev container on
DEV_PORT   ?= 28080

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

# Optioneel: BuildKit (voor docker); Podman negeert 't gewoon
export DOCKER_BUILDKIT ?= 1

# =========================
# Targets
# =========================
.PHONY: help dev ensure-dev-image dev-build dev-rebuild prod prod-rebuild push deploy print

help:
	@echo "Targets:"
	@echo "  dev            - Run dev container; build alleen als image nog niet bestaat"
	@echo "  dev-build      - Forceer (re)build van dev image"
	@echo "  dev-rebuild    - Rebuild dev image zonder cache"
	@echo "  prod           - Build production image (linux/amd64) met versie $(VERSION)"
	@echo "  prod-rebuild   - Rebuild production image zonder cache"
	@echo "  push           - Push production image naar Harbor"
	@echo "  deploy         - Helm upgrade/install met image $(PROD_IMAGE)"
	@echo "  print          - Print variabelen"

# --------------------------------------------
# DEV: Lazy build (alleen als image ontbreekt)
# --------------------------------------------
# dev-build (native arch)
dev-build:
	@if [ "$(ENGINE)" = "docker" ]; then \
	  $(ENGINE) build -t $(DEV_IMAGE) -f $(DOCKERFILE) --platform linux/$(BUILD_ARCH) . ; \
	else \
	  $(ENGINE) build -t $(DEV_IMAGE) -f $(DOCKERFILE) --arch $(BUILD_ARCH) --os linux . ; \
	fi

# dev (lazy build)
dev: ensure-dev-image
	@echo "Running dev container http://0.0.0.0:$(DEV_PORT). Don't get confused by the internal port 8080."
	$(ENGINE) run --rm -v "$$PWD/app":/app:Z,rw,U -p $(DEV_PORT):8080 $(DEV_IMAGE)

ensure-dev-image:
	@if ! $(ENGINE) image inspect $(DEV_IMAGE) >/dev/null 2>&1; then \
	  $(MAKE) dev-build; \
	else \
	  echo "✅ Dev image exists: $(DEV_IMAGE)"; \
	fi


# Build zonder cache
dev-rebuild:
	$(ENGINE) build --no-cache -t $(DEV_IMAGE) -f $(DOCKERFILE) .

# --------------------------------------------
# PROD: Build & Rebuild
# --------------------------------------------
prod:
	# Podman: --arch/--os; Docker: gebruik --platform
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
# PUSH & DEPLOY
# --------------------------------------------
push:
	$(ENGINE) push $(PROD_IMAGE)

deploy:
	helm upgrade --install $(HELM_INSTALLATION_NAME) $(CHART_DIR) \
		--namespace $(NAMESPACE) --create-namespace \
		--set image.repository=$(IMAGE_REPO) \
		--set image.tag=$(VERSION) \
		--set namespace=$(NAMESPACE) \
		--set hostname=$(HOSTNAME) \
		--set tls.secret=$(TLS_SECRET)

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

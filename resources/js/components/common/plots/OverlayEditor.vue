<template>
    <div class="editor-container">
        <div class="panzoom-parent" ref="panzoomParent">
            <div
                class="panzoom"
                ref="elem"
                :style="{
                    width: '100%',
                    height: '100%',
                    margin: '0 auto',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    position: 'relative',
                }"
            >
                <div
                    ref="elemTwo"
                    :style="{
                        width: overlayWidth + 'px',
                        height: overlayHeight + 'px',
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        pointerEvents: isLocked ? 'none' : 'auto',
                        opacity: overlayOpacity,
                    }"
                >
                    <img
                        :src="overlay"
                        id="overlayImage"
                        class="overlay-img-fluid"
                        :style="{
                            position: 'absolute',
                            width: '100%',
                            height: '100%',
                        }"
                    />
                </div>
                <img
                    :src="base"
                    id="baseImage"
                    class="img-fluid"
                    :style="{
                        position: 'relative',
                        zIndex: '-1',
                        opacity: baseOpacity,
                    }"
                />
            </div>
        </div>

        <div class="buttons-panel-right" v-if="showControls">
            <div class="btn-group-vertical">
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="adjustScale(true)"
                    title="Zoom In"
                >
                    <i class="fas fa-search-plus"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="adjustScale(false)"
                    title="Zoom Out"
                >
                    <i class="fas fa-search-minus"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    @click.prevent="resetZoom"
                    title="Reset Zoom"
                >
                    <i class="fas fa-redo"></i>
                </button>
                <button
                    class="btn btn-sm btn-info"
                    @click.prevent="resizeOverlay(true)"
                    :disabled="isLocked"
                    title="Increase Overlay Size"
                >
                    <i class="fas fa-expand"></i>
                </button>
                <button
                    class="btn btn-sm btn-info"
                    @click.prevent="resizeOverlay(false)"
                    :disabled="isLocked"
                    title="Decrease Overlay Size"
                >
                    <i class="fas fa-compress"></i>
                </button>

                <button
                    class="btn btn-sm btn"
                    @click.prevent="increaseOverlayWidth"
                    :disabled="isLocked"
                    title="Increase Overlay Width"
                >
                    <i class="fas fa-arrows-alt-h"></i>
                </button>
                <button
                    class="btn btn-sm btn"
                    @click.prevent="decreaseOverlayWidth"
                    :disabled="isLocked"
                    title="Decrease Overlay Width"
                >
                    <i class="fas fa-compress-alt"></i>
                </button>

                <button
                    class="btn btn-sm btn-warning"
                    @click.prevent="toggleLock"
                    title="Toggle Lock"
                >
                    <i :class="isLocked ? 'fas fa-lock' : 'fas fa-unlock'"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="moveOverlay('up')"
                    title="Move Up"
                >
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="moveOverlay('down')"
                    title="Move Down"
                >
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="moveOverlay('left')"
                    title="Move Left"
                >
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="moveOverlay('right')"
                    title="Move Right"
                >
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="exportState"
                    title="Export State"
                >
                    <i class="fas fa-download"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    @click.prevent="importState"
                    title="Import State"
                >
                    <i class="fas fa-upload"></i>
                </button>
            </div>
        </div>

        <div class="sliders-panel" v-if="showControls">
            <div class="slider-group">
                <label for="opacitySlider">Overlay Opacity</label>
                <input
                    id="opacitySlider"
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    v-model="overlayOpacity"
                />
                <label for="baseSlider">Base Opacity</label>
                <input
                    id="baseOpacity"
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    v-model="baseOpacity"
                />
            </div>
        </div>

        <div class="minimap-container" v-show="!isMinimapHidden">
            <div class="minimap" ref="minimap">
                <img
                    id="minimapBaseImage"
                    :src="base"
                    :style="minimapImageStyle"
                />
                <div
                    class="minimap-viewport"
                    :style="minimapViewportStyle"
                ></div>
            </div>
        </div>

        <div class="toggle-controls form-check form-switch">
            <input
                style="cursor: pointer"
                class="form-check-input"
                type="checkbox"
                id="toggleControls"
                @change="toggleControls"
                :checked="showControls"
            />
            <label class="form-check-label" for="toggleControls">
                {{ showControls ? "Hide Controls" : "Show Controls" }}
            </label>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, watch, nextTick, inject } from "vue";
import Panzoom from "@panzoom/panzoom";
import { Tooltip } from "bootstrap";

export default {
    name: "overlayEditor",
    props: {
        base: {
            type: String,
            required: true,
        },
        overlay: {
            type: String,
            required: true,
        },
    },

    setup(props, { emit }) {
        const sharedState = inject("sharedState");
        const elem = ref(null);
        const elemTwo = ref(null);
        const panzoomParent = ref(null);
        const overlayWidth = ref(sharedState.plotWidth / 1.5);
        const overlayHeight = ref(sharedState.plotHeight / 1.5);
        const overlayOpacity = ref(1);
        const baseOpacity = ref(1);
        const isLocked = ref(false);
        const isMinimapHidden = ref(true);
        const minimap = ref(null);
        const minimapViewport = ref(null);
        const showControls = ref(true);
        let panzoom = null;
        let panzoom2 = null;
        const MAX_ZOOM_SCALE = 2.4596031111569503;

        const minimapImageStyle = {
            width: "100%",
            height: "100%",
        };

        const minimapViewportStyle = ref({
            width: "50px",
            height: "50px",
            top: "0px",
            left: "0px",
        });

        // TODO: Review this when we need to make it responsive
        // const updateOverlaySize = () => {
        //     if (panzoomParent.value) {
        //         const parentRect = panzoomParent.value.getBoundingClientRect();
        //         overlayWidth.value = parentRect.width / 1.5;
        //         overlayHeight.value = parentRect.width / 1.5;
        //     }
        // };

        function applyZoomTransform(transform) {
            const { x, y, scale } = transform;
            panzoom.zoom(scale);
            panzoom.pan(x, y, { force: true });
        }

        const updateMinimapViewport = () => {
            const baseImage = document.getElementById("baseImage");
            const minimapBaseImage =
                document.getElementById("minimapBaseImage");

            const baseImageWidth = baseImage.naturalWidth;
            const baseImageHeight = baseImage.naturalHeight;
            const minimapBaseImageWidth = minimapBaseImage.width;
            const minimapBaseImageHeight = minimapBaseImage.height;

            const scaleX = minimapBaseImageWidth / baseImageWidth;
            const scaleY = minimapBaseImageHeight / baseImageHeight;

            const scale = panzoom.getScale();
            const pan = panzoom.getPan();

            const minimapViewportWidth =
                ((minimapBaseImageWidth / scale) * 100) / minimapBaseImageWidth;
            const minimapViewportHeight =
                ((minimapBaseImageHeight / scale) * 100) /
                minimapBaseImageHeight;

            const minimapViewportLeft = -pan.x * scale * scaleX;
            const minimapViewportTop = -pan.y * scale * scaleY;

            minimapViewportStyle.value = {
                width: `${minimapViewportWidth}%`,
                height: `${minimapViewportHeight}%`,
                top: `${minimapViewportTop}px`,
                left: `${minimapViewportLeft}px`,
            };

            if (scale <= 1.1) {
                isMinimapHidden.value = true;
            } else {
                isMinimapHidden.value = false;
            }

            emit("zoom-pan", {
                transformElement: { x: pan.x, y: pan.y, scale: scale },
                source: "overlay",
            });
        };

        onMounted(() => {
            // updateOverlaySize();
            // window.addEventListener("resize", updateOverlaySize);
            nextTick(() => {
                panzoom = Panzoom(elem.value, {
                    maxScale: MAX_ZOOM_SCALE,
                });
                panzoom2 = Panzoom(elemTwo.value, {
                    setTransform: (elem, { x, y, scale }) => {
                        const parentScale = panzoom.getScale();
                        panzoom2.setStyle(
                            "transform",
                            `scale(${scale}) translate(${x / parentScale}px, ${
                                y / parentScale
                            }px)`
                        );
                    },
                });

                elem.value.addEventListener(
                    "panzoompan",
                    updateMinimapViewport
                );
                elem.value.addEventListener(
                    "panzoomzoom",
                    updateMinimapViewport
                );

                elem.value.addEventListener("wheel", function (event) {
                    if (!event.shiftKey) return;
                    panzoom.zoomWithWheel(event);
                });

                const parentRect = elem.value.getBoundingClientRect();
                const elemTwoRect = elemTwo.value.getBoundingClientRect();

                const centerX = (parentRect.width - elemTwoRect.width) / 2;
                const centerY = (parentRect.height - elemTwoRect.height) / 2;

                panzoom2.pan(centerX, centerY, { force: true });

                updateMinimapViewport();

                const tooltipTriggerList = [].slice.call(
                    document.querySelectorAll("[title]")
                );
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new Tooltip(tooltipTriggerEl, {
                        trigger: "hover",
                    });
                });
            });
        });

        watch(isLocked, (newVal) => {
            const panzoomElem = elemTwo.value;
            if (newVal) {
                panzoomElem.style.pointerEvents = "none";
            } else {
                panzoomElem.style.pointerEvents = "auto";
            }

            emit("toggle-lock", newVal);
        });

        function adjustScale(zoomIn) {
            const zoomFactor = 1.2;
            const oldScale = panzoom.getScale();

            if (zoomIn) {
                panzoom.zoomIn(zoomFactor);
            } else {
                panzoom.zoomOut(zoomFactor);
            }
            const newScale = panzoom.getScale();
            const pan = panzoom2.getPan();

            panzoom2.pan(
                (pan.x / oldScale) * newScale,
                (pan.y / oldScale) * newScale,
                {
                    animate: true,
                }
            );

            updateMinimapViewport();
        }

        function resizeOverlay(increase) {
            const increment = 5;
            if (!isLocked.value) {
                if (increase) {
                    overlayWidth.value += increment;
                    overlayHeight.value += increment;
                } else {
                    overlayWidth.value -= increment;
                    overlayHeight.value -= increment;
                }
            }
        }

        function moveOverlay(direction) {
            const increment = 0.5;
            const currentPan = isLocked.value
                ? panzoom.getPan()
                : panzoom2.getPan();

            switch (direction) {
                case "up":
                    if (isLocked.value) {
                        panzoom.pan(currentPan.x, currentPan.y - increment, {
                            force: true,
                        });
                    } else {
                        panzoom2.pan(currentPan.x, currentPan.y - increment, {
                            force: true,
                        });
                    }
                    break;
                case "down":
                    if (isLocked.value) {
                        panzoom.pan(currentPan.x, currentPan.y + increment, {
                            force: true,
                        });
                    } else {
                        panzoom2.pan(currentPan.x, currentPan.y + increment, {
                            force: true,
                        });
                    }
                    break;
                case "left":
                    if (isLocked.value) {
                        panzoom.pan(currentPan.x - increment, currentPan.y, {
                            force: true,
                        });
                    } else {
                        panzoom2.pan(currentPan.x - increment, currentPan.y, {
                            force: true,
                        });
                    }
                    break;
                case "right":
                    if (isLocked.value) {
                        panzoom.pan(currentPan.x + increment, currentPan.y, {
                            force: true,
                        });
                    } else {
                        panzoom2.pan(currentPan.x + increment, currentPan.y, {
                            force: true,
                        });
                    }
                    break;
            }
            updateMinimapViewport();
        }

        function resetZoom() {
            const overlayPan = panzoom2.getPan();
            const overlayScale = panzoom2.getScale();

            panzoom.zoom(1);
            panzoom.pan(0, 0);

            panzoom2.zoom(overlayScale);
            panzoom2.pan(overlayPan.x, overlayPan.y, { force: true });

            updateMinimapViewport();
            importState();
        }

        function toggleLock() {
            isLocked.value = !isLocked.value;
        }

        function toggleControls() {
            showControls.value = !showControls.value;
        }

        function increaseOverlayWidth() {
            emit("update-overlay-width", true);
        }

        function decreaseOverlayWidth() {
            emit("update-overlay-width", false);
        }

        function exportState() {
            const state = {
                overlayWidth: overlayWidth.value,
                overlayHeight: overlayHeight.value,
                overlayOpacity: overlayOpacity.value,
                baseOpacity: baseOpacity.value,
                plotHeight: sharedState.plotHeight,
                plotWidth: sharedState.plotWidth,
                pan: panzoom.getPan(),
                scale: panzoom.getScale(),
                overlayPan: panzoom2.getPan(),
                overlayScale: panzoom2.getScale(),
                isLocked: isLocked.value,
            };
            const stateStr = JSON.stringify(state);
            console.log("Exported State:", stateStr);
        }

        function importState() {
            const state = {
                overlayWidth: 478.5949300130208,
                overlayHeight: 478.5949300130208,
                overlayOpacity: 1,
                baseOpacity: 1,
                plotHeight: 480,
                plotWidth: 520,
                pan: { x: 0, y: 0 },
                scale: 1,
                overlayPan: { x: -69.5458984375, y: -77.83076477050781 },
                overlayScale: 1,
                isLocked: true,
            };

            overlayWidth.value = state.overlayWidth;
            overlayHeight.value = state.overlayHeight;
            overlayOpacity.value = state.overlayOpacity;
            baseOpacity.value = state.baseOpacity;
            sharedState.plotHeight = state.plotHeight;
            sharedState.plotWidth = state.plotWidth;
            isLocked.value = state.isLocked;

            panzoom.pan(state.pan.x, state.pan.y, { force: true });
            panzoom.zoom(state.scale, { animate: true });

            panzoom2.pan(state.overlayPan.x, state.overlayPan.y, {
                force: true,
            });
            panzoom2.zoom(state.overlayScale, { animate: true });

            emit("update-plot", true);

            updateMinimapViewport();
        }

        return {
            elem,
            elemTwo,
            panzoomParent,
            overlayWidth,
            overlayHeight,
            overlayOpacity,
            baseOpacity,
            increaseOverlayWidth,
            decreaseOverlayWidth,
            isLocked,
            adjustScale,
            resizeOverlay,
            moveOverlay,
            resetZoom,
            toggleLock,
            exportState,
            importState,
            minimap,
            minimapViewport,
            minimapImageStyle,
            minimapViewportStyle,
            isMinimapHidden,
            showControls,
            toggleControls,
            applyZoomTransform,
        };
    },
};
</script>

<style>
.editor-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 100%;
    height: 100%;
}

.buttons-panel-right {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: absolute;
    right: 5px;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.sliders-panel {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.8rem;
}

.slider-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.panzoom-parent {
    position: relative;
    width: 100%;
    height: 100%;
    margin: 0 auto;
}

.panzoom {
    position: relative;
    width: 100%;
    height: 100%;
}

.minimap-container {
    top: 10px;
    position: absolute;
    left: 20px;
    z-index: 2;
}

.overlay-img-fluid {
    max-width: 90% !important;
}

.img-fluid {
    max-width: 80% !important;
}

.minimap {
    position: relative;
    width: 100px;
    height: 100px;
    overflow: hidden;
    border: 1px solid #ccc;
}

.minimap img {
    width: 100%;
    height: 100%;
}

.minimap-viewport {
    position: absolute;
    border: 2px solid red;
    box-sizing: border-box;
}

.toggle-controls {
    position: absolute;
    bottom: -5px;
    right: 10px;
    align-items: center;
    border-radius: 8px;
}
</style>

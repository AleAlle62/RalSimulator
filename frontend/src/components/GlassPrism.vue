<template>
  <div ref="host" class="prism" aria-hidden="true" />
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';

/**
 * A slowly turning glass solid, next to the sign-in form.
 *
 * Decorative and marked aria-hidden: it carries no information, so a screen reader that skips
 * it loses nothing. Under prefers-reduced-motion no scene is created at all — this is a
 * continuous animation and a WebGL context, and the setting exists to stop exactly that.
 *
 * Everything is disposed on unmount. A dropped WebGL context is not garbage collected on its
 * own, and leaving one behind on every visit to /accedi would leak the GPU memory of a whole
 * scene each time.
 */

const host = ref<HTMLDivElement | null>(null);

let renderer: THREE.WebGLRenderer | null = null;
let frame = 0;
let disposeScene: (() => void) | null = null;

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

onMounted(() => {
  const container = host.value;
  if (!container || prefersReducedMotion) return;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.z = 5;

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  container.appendChild(renderer.domElement);

  // An octahedron rather than a cube: more facets, so the light breaks across it as it turns
  // and the solid reads as glass instead of as a box.
  const geometry = new THREE.OctahedronGeometry(1.5, 0);
  const material = new THREE.MeshPhysicalMaterial({
    color: new THREE.Color('#7fb2ff'),
    metalness: 0.1,
    roughness: 0.08,
    transmission: 0.92,
    thickness: 1.4,
    ior: 1.45,
    transparent: true,
  });

  const solid = new THREE.Mesh(geometry, material);
  scene.add(solid);

  const edges = new THREE.LineSegments(
    new THREE.EdgesGeometry(geometry),
    new THREE.LineBasicMaterial({
      color: new THREE.Color('#e8eef7'),
      transparent: true,
      opacity: 0.35,
    }),
  );
  solid.add(edges);

  const key = new THREE.DirectionalLight('#ffffff', 3);
  key.position.set(3, 4, 5);
  const rim = new THREE.DirectionalLight('#f0c070', 2);
  rim.position.set(-4, -2, -3);
  scene.add(key, rim, new THREE.AmbientLight('#3c6fc4', 1.2));

  const pointer = { x: 0, y: 0 };
  const onPointerMove = (event: PointerEvent) => {
    const bounds = container.getBoundingClientRect();
    pointer.x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 2;
    pointer.y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 2;
  };
  window.addEventListener('pointermove', onPointerMove);

  const resize = () => {
    const { clientWidth, clientHeight } = container;
    if (!clientWidth || !clientHeight || !renderer) return;
    renderer.setSize(clientWidth, clientHeight, false);
    camera.aspect = clientWidth / clientHeight;
    camera.updateProjectionMatrix();
  };

  const observer = new ResizeObserver(resize);
  observer.observe(container);
  resize();

  const clock = new THREE.Clock();
  const tick = () => {
    const elapsed = clock.getElapsedTime();

    solid.rotation.y = elapsed * 0.35 + pointer.x * 0.4;
    solid.rotation.x = Math.sin(elapsed * 0.25) * 0.25 + pointer.y * 0.3;
    solid.position.y = Math.sin(elapsed * 0.6) * 0.12;

    renderer?.render(scene, camera);
    frame = requestAnimationFrame(tick);
  };
  frame = requestAnimationFrame(tick);

  disposeScene = () => {
    window.removeEventListener('pointermove', onPointerMove);
    observer.disconnect();
    geometry.dispose();
    material.dispose();
    edges.geometry.dispose();
    (edges.material as THREE.Material).dispose();
  };
});

onUnmounted(() => {
  cancelAnimationFrame(frame);
  disposeScene?.();

  if (renderer) {
    renderer.domElement.remove();
    renderer.dispose();
    renderer.forceContextLoss();
    renderer = null;
  }
});
</script>

<style scoped lang="scss">
.prism {
  width: 100%;
  height: 100%;
  min-height: 18rem;
}
</style>

<template>
  <div ref="host" class="euro" aria-hidden="true" />
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';

/**
 * A euro sign in glass, turning slowly next to the sign-in form.
 *
 * The glyph is built from primitives rather than extruded from a typeface: an annular sector for
 * the C and two rectangles for the bars. That keeps the component free of a font asset and of the
 * licensing question that comes with shipping outlines lifted out of one, and the arcs stay
 * parametric — the proportions below are numbers to tune, not a curve someone else drew.
 *
 * The three solids intersect on purpose. Extrude has no boolean union, so the bars pass through
 * the ring, and in a transmissive material those internal surfaces refract: the result reads as a
 * faceted glass object rather than as a flat sticker of a euro sign.
 *
 * Decorative and marked aria-hidden: it carries no information, so a screen reader that skips it
 * loses nothing. Under prefers-reduced-motion no scene is created at all — this is a continuous
 * animation and a WebGL context, and the setting exists to stop exactly that.
 *
 * Everything is disposed on unmount. A dropped WebGL context is not garbage collected on its own,
 * and leaving one behind on every visit to /accedi would leak the GPU memory of a whole scene
 * each time.
 */

const host = ref<HTMLDivElement | null>(null);

let renderer: THREE.WebGLRenderer | null = null;
let frame = 0;
let disposeScene: (() => void) | null = null;

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** The C: an annulus with a wedge missing on the right, where the bars come through. */
function buildRing(): THREE.Shape {
  const outerRadius = 1;
  const innerRadius = 0.68;
  const mouthHalfAngle = THREE.MathUtils.degToRad(40);

  const shape = new THREE.Shape();
  shape.absarc(0, 0, outerRadius, mouthHalfAngle, Math.PI * 2 - mouthHalfAngle, false);
  shape.absarc(0, 0, innerRadius, Math.PI * 2 - mouthHalfAngle, mouthHalfAngle, true);
  shape.closePath();

  return shape;
}

/**
 * A bar, crossing the C and overhanging it on the left.
 *
 * The overhang is what makes the sign a euro instead of a struck-through C: on the left the bars
 * have to clear the ring, on the right they stop inside the mouth.
 */
function buildBar(centreY: number): THREE.Shape {
  const left = -1.28;
  const right = 0.36;
  // Two thirds of the ring's stroke. Matched to it the sign reads as a grid; much under this and
  // the bars thin out to hairlines once the whole thing is turned away from the camera.
  const halfHeight = 0.105;

  const shape = new THREE.Shape();
  shape.moveTo(left, centreY - halfHeight);
  shape.lineTo(right, centreY - halfHeight);
  shape.lineTo(right, centreY + halfHeight);
  shape.lineTo(left, centreY + halfHeight);
  shape.closePath();

  return shape;
}

onMounted(() => {
  const container = host.value;
  if (!container || prefersReducedMotion) return;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.z = 5;

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  container.appendChild(renderer.domElement);

  // setSize(..., false) below skips the CSS-pixel style Three.js would otherwise set, so
  // without this the canvas falls back to its width/height attributes — set in device pixels,
  // up to 2x too large on a HiDPI screen — as its on-screen size, overflowing `.euro` instead of
  // filling it. Same fix as Silk.vue applies to its own raw canvas.
  renderer.domElement.style.width = '100%';
  renderer.domElement.style.height = '100%';
  renderer.domElement.style.display = 'block';

  const geometry = new THREE.ExtrudeGeometry([buildRing(), buildBar(0.2), buildBar(-0.2)], {
    depth: 0.42,
    curveSegments: 48,
    bevelEnabled: true,
    bevelThickness: 0.05,
    bevelSize: 0.05,
    bevelSegments: 3,
  });
  // Extrude builds forward from z = 0 and the bars are not symmetric about the origin, so without
  // this the sign would orbit a point off to one side of itself instead of turning in place.
  geometry.center();

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
    new THREE.EdgesGeometry(geometry, 24),
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

    // The octahedron this replaced could spin freely; a sign cannot. An extrusion turned past
    // about a quarter turn is seen edge-on and stops being a euro, so the yaw swings inside a
    // range that always keeps the glyph legible and the pointer nudges it within that range.
    solid.rotation.y = Math.sin(elapsed * 0.4) * 0.5 + pointer.x * 0.35;
    solid.rotation.x = Math.sin(elapsed * 0.25) * 0.12 + pointer.y * 0.2;
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
.euro {
  width: 100%;
  height: 100%;
  min-height: 18rem;
}
</style>

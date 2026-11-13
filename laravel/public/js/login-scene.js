'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('three-container');
    const showcase = document.querySelector('.showcase');
    if (!container || !showcase || typeof THREE === 'undefined') return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x0d1629, 0.032);

    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.set(6.8, 5.2, 8.6);
    camera.lookAt(0, 0.6, 0);

    const isMobile = window.matchMedia('(max-width: 1023px)');
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance'
        });
    } catch (error) {
        return;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile.matches ? 1.5 : 2));
    renderer.shadowMap.enabled = !isMobile.matches;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    container.appendChild(renderer.domElement);
    document.body.classList.add('has-webgl');

    scene.add(new THREE.AmbientLight(0x526a9b, 1.15));
    const keyLight = new THREE.DirectionalLight(0xffedd5, 2.1);
    keyLight.position.set(7, 11, 6);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.set(1024, 1024);
    keyLight.shadow.camera.near = 1;
    keyLight.shadow.camera.far = 30;
    scene.add(keyLight);
    const rimLight = new THREE.DirectionalLight(0x60a5fa, 1.1);
    rimLight.position.set(-8, 6, -6);
    scene.add(rimLight);
    const warmLight = new THREE.PointLight(0xf59e0b, 2, 10);
    warmLight.position.set(0, 1.6, 0.5);
    scene.add(warmLight);

    const matNavy = new THREE.MeshStandardMaterial({ color: 0x14213d, roughness: 0.55, metalness: 0.25 });
    const matDark = new THREE.MeshStandardMaterial({ color: 0x0b1324, roughness: 0.4, metalness: 0.5 });
    const matGold = new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.25, metalness: 0.82 });
    const matEmerald = new THREE.MeshStandardMaterial({ color: 0x059669, roughness: 0.45, metalness: 0.15 });
    const matCream = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.35, metalness: 0.1 });
    const matCoral = new THREE.MeshStandardMaterial({ color: 0xea580c, roughness: 0.35, metalness: 0.2 });
    const matGlass = new THREE.MeshPhysicalMaterial({
        color: 0x93c5fd,
        transparent: true,
        opacity: 0.24,
        roughness: 0.1,
        metalness: 0.1,
        reflectivity: 0.9
    });

    const desktopGroup = new THREE.Group();
    const mobileGroup = new THREE.Group();
    desktopGroup.scale.setScalar(0.76);
    scene.add(desktopGroup, mobileGroup);

    function addBox(parent, width, height, depth, material, x, y, z, castShadow) {
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material);
        mesh.position.set(x, y, z);
        mesh.castShadow = castShadow !== false;
        mesh.receiveShadow = true;
        parent.add(mesh);
        return mesh;
    }

    // Escena amplia de tienda, estanterías y mostrador para escritorio y laptop.
    const floor = addBox(desktopGroup, 6.2, 0.35, 5.4, matDark, 0, -0.18, 0, false);
    floor.receiveShadow = true;
    addBox(desktopGroup, 6.28, 0.06, 5.48, matGold, 0, -0.02, 0, false);

    const backWall = addBox(desktopGroup, 4.4, 2.7, 0.22, matNavy, -0.3, 1.35, -1.6);
    const sideWall = addBox(desktopGroup, 0.22, 2.7, 3.4, matNavy, -2.4, 1.35, 0);
    backWall.castShadow = sideWall.castShadow = true;
    addBox(desktopGroup, 4.7, 0.2, 3.7, matDark, -0.25, 2.75, 0.05);
    const glass = new THREE.Mesh(new THREE.BoxGeometry(0.08, 2.2, 3), matGlass);
    glass.position.set(1.85, 1.15, 0.1);
    desktopGroup.add(glass);

    const awning = new THREE.Group();
    awning.position.set(2.15, 2.35, 0.1);
    awning.rotation.z = -0.35;
    desktopGroup.add(awning);
    const stripeCount = 7;
    const stripeDepth = 3.2 / stripeCount;
    for (let index = 0; index < stripeCount; index++) {
        const stripe = addBox(awning, 0.95, 0.12, stripeDepth - 0.02, index % 2 === 0 ? matEmerald : matCream, 0, 0, -1.6 + stripeDepth * (index + 0.5));
        stripe.castShadow = true;
    }

    const shelves = new THREE.Group();
    shelves.position.set(-0.5, 0, -1.1);
    desktopGroup.add(shelves);
    for (let level = 0; level < 3; level++) {
        addBox(shelves, 3.4, 0.08, 0.75, matDark, 0, 0.55 + level * 0.72, 0, true);
        for (let item = 0; item < 4; item++) {
            if (level === 2 && item === 3) continue;
            const height = 0.36 + ((item + level) % 2) * 0.12;
            const material = (item + level) % 3 === 0 ? matGold : ((item + level) % 2 === 0 ? matCream : matNavy);
            addBox(shelves, 0.55, height, 0.5, material, -1.2 + item * 0.8, 0.59 + level * 0.72 + height / 2, 0, true);
        }
    }
    addBox(desktopGroup, 0.9, 1, 2.2, matDark, 0.65, 0.5, 0.25, true);
    addBox(desktopGroup, 1, 0.08, 2.3, matGold, 0.65, 1.02, 0.25, true);

    const floatingTools = new THREE.Group();
    floatingTools.position.set(-1.3, 3.65, 1.2);
    desktopGroup.add(floatingTools);
    const rulerShape = new THREE.Shape();
    rulerShape.moveTo(0, 0);
    rulerShape.lineTo(1.5, 0);
    rulerShape.lineTo(0, 1.5);
    rulerShape.closePath();
    const rulerHole = new THREE.Path();
    rulerHole.moveTo(0.28, 0.28);
    rulerHole.lineTo(0.78, 0.28);
    rulerHole.lineTo(0.28, 0.78);
    rulerHole.closePath();
    rulerShape.holes.push(rulerHole);
    const ruler = new THREE.Mesh(new THREE.ExtrudeGeometry(rulerShape, {
        depth: 0.12, bevelEnabled: true, bevelThickness: 0.03, bevelSize: 0.03, bevelSegments: 3
    }), matGold);
    ruler.castShadow = true;
    floatingTools.add(ruler);

    const desktopPencil = new THREE.Group();
    desktopPencil.position.set(1.75, 0.7, 0.15);
    desktopPencil.rotation.z = -0.32;
    floatingTools.add(desktopPencil);
    addBox(desktopPencil, 0.22, 1.1, 0.22, matCoral, 0, 0, 0, true);
    const desktopTip = new THREE.Mesh(new THREE.ConeGeometry(0.16, 0.34, 6), matCream);
    desktopTip.position.y = -0.7;
    desktopTip.rotation.x = Math.PI;
    desktopPencil.add(desktopTip);
    addBox(desktopPencil, 0.22, 0.2, 0.22, new THREE.MeshStandardMaterial({ color: 0xf43f5e, roughness: 0.5 }), 0, 0.64, 0, false);

    function createRedPaperclip() {
        const points = [];
        const addArc = (centerX, centerY, radius, startAngle, endAngle, steps) => {
            for (let index = 0; index <= steps; index++) {
                const angle = startAngle + (endAngle - startAngle) * (index / steps);
                points.push(new THREE.Vector3(centerX + Math.cos(angle) * radius, centerY + Math.sin(angle) * radius, 0));
            }
        };

        points.push(new THREE.Vector3(-0.18, -0.25, 0));
        addArc(0, 0.35, 0.18, Math.PI, 0, 18);
        addArc(0.03, -0.35, 0.15, 0, -Math.PI, 18);
        addArc(0.01, 0.28, 0.11, Math.PI, 0, 16);
        addArc(0.01, -0.22, 0.08, 0, -Math.PI, 16);
        points.push(new THREE.Vector3(-0.07, 0.18, 0));

        const curve = new THREE.CatmullRomCurve3(points);
        const geometry = new THREE.TubeGeometry(curve, 90, 0.032, 16, false);
        const material = new THREE.MeshStandardMaterial({ color: 0xe63928, roughness: 0.25, metalness: 0.25 });
        const clip = new THREE.Mesh(geometry, material);
        clip.castShadow = true;
        clip.receiveShadow = true;
        return clip;
    }

    const redPaperclip = createRedPaperclip();
    redPaperclip.position.set(-0.08, 0.42, 0.38);
    redPaperclip.rotation.z = 0.55;
    floatingTools.add(redPaperclip);

    // Emblema compacto para el panel superior de celulares y tablets.
    const pedestal = new THREE.Mesh(new THREE.CylinderGeometry(1.45, 1.55, 0.14, 36), matNavy);
    pedestal.position.y = -0.85;
    mobileGroup.add(pedestal);
    const ring = new THREE.Mesh(new THREE.TorusGeometry(1.48, 0.025, 12, 48), matGold);
    ring.rotation.x = Math.PI / 2;
    ring.position.y = -0.78;
    mobileGroup.add(ring);

    const mobileAwning = new THREE.Group();
    mobileAwning.position.set(0, 0.65, -0.1);
    mobileAwning.rotation.x = 0.32;
    mobileGroup.add(mobileAwning);
    const totalWidth = 2.35;
    const mobileStripes = 7;
    const stripeWidth = totalWidth / mobileStripes;
    for (let index = 0; index < mobileStripes; index++) {
        const material = index % 2 === 0 ? matEmerald : matCream;
        const stripe = addBox(mobileAwning, stripeWidth - 0.015, 0.12, 0.95, material, -totalWidth / 2 + stripeWidth * (index + 0.5), 0, 0, true);
        const valance = new THREE.Mesh(new THREE.CylinderGeometry(stripeWidth * 0.48, stripeWidth * 0.48, 0.12, 16), material);
        valance.position.set(stripe.position.x, -0.02, 0.48);
        mobileAwning.add(valance);
    }

    const mobileRulerShape = new THREE.Shape();
    mobileRulerShape.moveTo(0, 0);
    mobileRulerShape.lineTo(1.15, 0);
    mobileRulerShape.lineTo(0, 1.15);
    mobileRulerShape.closePath();
    const mobileRulerHole = new THREE.Path();
    mobileRulerHole.moveTo(0.22, 0.22);
    mobileRulerHole.lineTo(0.62, 0.22);
    mobileRulerHole.lineTo(0.22, 0.62);
    mobileRulerHole.closePath();
    mobileRulerShape.holes.push(mobileRulerHole);
    const mobileRuler = new THREE.Mesh(new THREE.ExtrudeGeometry(mobileRulerShape, {
        depth: 0.1, bevelEnabled: true, bevelThickness: 0.025, bevelSize: 0.025, bevelSegments: 2
    }), matGold);
    mobileRuler.position.set(-0.72, -0.58, 0.12);
    mobileGroup.add(mobileRuler);

    const mobileClipPoints = [
        new THREE.Vector3(-0.15, -0.22, 0), new THREE.Vector3(-0.15, 0.30, 0),
        new THREE.Vector3(0, 0.44, 0), new THREE.Vector3(0.15, 0.30, 0),
        new THREE.Vector3(0.15, -0.30, 0), new THREE.Vector3(0.02, -0.42, 0),
        new THREE.Vector3(-0.10, -0.30, 0), new THREE.Vector3(-0.10, 0.22, 0),
        new THREE.Vector3(0, 0.31, 0), new THREE.Vector3(0.09, 0.22, 0),
        new THREE.Vector3(0.09, -0.18, 0), new THREE.Vector3(0.02, -0.25, 0),
        new THREE.Vector3(-0.04, -0.18, 0), new THREE.Vector3(-0.04, 0.14, 0)
    ];
    const mobileClipCurve = new THREE.CatmullRomCurve3(mobileClipPoints);
    const mobileClipGeometry = new THREE.TubeGeometry(mobileClipCurve, 72, 0.032, 12, false);
    const mobileClipMaterial = new THREE.MeshStandardMaterial({ color: 0xe63928, roughness: 0.25, metalness: 0.2 });
    const mobileRedClip = new THREE.Mesh(mobileClipGeometry, mobileClipMaterial);
    mobileRedClip.position.set(-0.05, 0.18, 0.14);
    mobileRedClip.rotation.z = 0.56;
    mobileGroup.add(mobileRedClip);

    const mobilePencil = new THREE.Group();
    mobilePencil.position.set(0.65, -0.08, 0.22);
    mobilePencil.rotation.z = -0.35;
    mobileGroup.add(mobilePencil);
    const mobilePencilBody = new THREE.Mesh(new THREE.CylinderGeometry(0.085, 0.085, 1.05, 6), matCoral);
    mobilePencil.add(mobilePencilBody);
    const mobileTip = new THREE.Mesh(new THREE.ConeGeometry(0.085, 0.26, 6), matCream);
    mobileTip.position.y = -0.65;
    mobileTip.rotation.x = Math.PI;
    mobilePencil.add(mobileTip);
    const eraser = new THREE.Mesh(new THREE.CylinderGeometry(0.085, 0.085, 0.16, 12), new THREE.MeshStandardMaterial({ color: 0xf43f5e, roughness: 0.4 }));
    eraser.position.y = 0.6;
    mobilePencil.add(eraser);

    const particlePositions = new Float32Array(65 * 3);
    for (let index = 0; index < particlePositions.length; index += 3) {
        particlePositions[index] = (Math.random() - 0.5) * 11;
        particlePositions[index + 1] = Math.random() * 6;
        particlePositions[index + 2] = (Math.random() - 0.5) * 11;
    }
    const particleGeometry = new THREE.BufferGeometry();
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particles = new THREE.Points(particleGeometry, new THREE.PointsMaterial({ size: 0.06, color: 0xfbbf24, transparent: true, opacity: 0.65 }));
    scene.add(particles);

    let mobileMode = false;
    let pointerDown = false;
    let previousX = 0;
    let targetRotationY = 0;
    let targetRotationX = 0;
    let dragRotationY = 0;
    let frameId = 0;

    function applyMode() {
        mobileMode = isMobile.matches;
        desktopGroup.visible = !mobileMode;
        mobileGroup.visible = mobileMode;
        renderer.shadowMap.enabled = !mobileMode;
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobileMode ? 1.5 : 2));
        if (mobileMode) {
            camera.position.set(0, 1.35, window.innerWidth < 480 ? 5.7 : 5.35);
            camera.fov = 38;
            camera.lookAt(0, 0.15, 0);
        } else {
            camera.position.set(6.8, 5.2, 8.6);
            camera.fov = 42;
            camera.lookAt(0, 0.6, 0);
        }
        camera.updateProjectionMatrix();
        resize();
    }

    function resize() {
        const width = container.clientWidth;
        const height = container.clientHeight;
        if (!width || !height) return;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height);
    }

    container.addEventListener('pointerdown', function (event) {
        pointerDown = true;
        previousX = event.clientX;
        if (container.setPointerCapture) container.setPointerCapture(event.pointerId);
    });
    container.addEventListener('pointermove', function (event) {
        const bounds = container.getBoundingClientRect();
        const normalizedX = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
        const normalizedY = ((event.clientY - bounds.top) / bounds.height) * 2 - 1;
        if (pointerDown) {
            const deltaX = event.clientX - previousX;
            dragRotationY += deltaX * (mobileMode ? 0.015 : 0.008);
            previousX = event.clientX;
        } else if (!mobileMode) {
            targetRotationY = normalizedX * 0.35;
            targetRotationX = normalizedY * 0.14;
        }
    });
    function endPointer() { pointerDown = false; }
    container.addEventListener('pointerup', endPointer);
    container.addEventListener('pointercancel', endPointer);
    container.addEventListener('lostpointercapture', endPointer);
    window.addEventListener('resize', resize);
    isMobile.addEventListener('change', applyMode);
    applyMode();

    const clock = new THREE.Clock();
    function animate() {
        if (!container.isConnected) {
            cancelAnimationFrame(frameId);
            renderer.dispose();
            return;
        }
        if (document.hidden) return;
        const elapsed = clock.getElapsedTime();
        floatingTools.position.y = 3.65 + Math.sin(elapsed * 1.8) * 0.14;
        floatingTools.rotation.y = Math.sin(elapsed * 0.9) * 0.18;
        redPaperclip.position.y = 0.42 + Math.sin(elapsed * 2.2) * 0.03;
        mobilePencil.rotation.y = elapsed * 1.4;
        mobileGroup.position.y = Math.sin(elapsed * 2) * 0.06;
        mobileRedClip.position.y = 0.18 + Math.sin(elapsed * 2.2) * 0.03;
        warmLight.intensity = 2 + Math.sin(elapsed * 2.5) * 0.25;
        particles.rotation.y = elapsed * 0.04;

        if (!reducedMotion) {
            if (mobileMode) {
                const targetY = dragRotationY + Math.sin(elapsed * 0.65) * 0.32;
                mobileGroup.rotation.y += (targetY - mobileGroup.rotation.y) * 0.08;
            } else {
                const targetY = dragRotationY + targetRotationY + Math.sin(elapsed * 0.4) * 0.06;
                desktopGroup.rotation.y += (targetY - desktopGroup.rotation.y) * 0.06;
                desktopGroup.rotation.x += (targetRotationX - desktopGroup.rotation.x) * 0.06;
            }
        }
        renderer.render(scene, camera);
        if (reducedMotion) return;
        frameId = requestAnimationFrame(animate);
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !reducedMotion) animate();
    });
    animate();
});

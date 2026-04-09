export default class BaseLayer {
    constructor(map) {
        this.map = map;
    }

    onAdd() {} // called when layer is registered
    onRemove() {} // cleanup

    onMove(bounds) {} // map moved
    onZoom(zoom) {} // zoom changed

    destroy() {
        this.onRemove();
    }
}

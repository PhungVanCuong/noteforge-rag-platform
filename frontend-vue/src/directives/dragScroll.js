/**
 * Vue Directive: v-drag-scroll
 * Enables horizontal mouse-drag scrolling (click-and-hold left mouse button to drag/scroll).
 * Automatically suppresses child click events when dragging occurs.
 */

export const dragScroll = {
  mounted(el) {
    let isDown = false
    let startX = 0
    let startScrollLeft = 0
    let hasDragged = false

    // Check if element has horizontal overflow to apply grab cursor
    const updateCursor = () => {
      if (el.scrollWidth > el.clientWidth) {
        el.style.cursor = 'grab'
      } else {
        el.style.cursor = ''
      }
    }

    updateCursor()
    const resizeObserver = new ResizeObserver(updateCursor)
    resizeObserver.observe(el)

    const onMouseDown = (e) => {
      // Only handle main (left) mouse button
      if (e.button !== 0) return

      // Don't drag if element has no scrollable content
      if (el.scrollWidth <= el.clientWidth) return

      isDown = true
      hasDragged = false
      startX = e.pageX - el.offsetLeft
      startScrollLeft = el.scrollLeft

      el.style.cursor = 'grabbing'
      el.style.userSelect = 'none'
      el.classList.add('is-drag-scrolling')
    }

    const onMouseMove = (e) => {
      if (!isDown) return

      const x = e.pageX - el.offsetLeft
      const walk = (x - startX) * 1.2 // Scroll sensitivity

      if (Math.abs(x - startX) > 5) {
        hasDragged = true
      }

      if (hasDragged) {
        e.preventDefault()
        el.scrollLeft = startScrollLeft - walk
      }
    }

    const onMouseUp = () => {
      if (!isDown) return
      isDown = false

      el.style.cursor = el.scrollWidth > el.clientWidth ? 'grab' : ''
      el.style.userSelect = ''
      el.classList.remove('is-drag-scrolling')

      if (hasDragged) {
        // Prevent click events triggered by mouseup on buttons/chips inside the container
        const captureClick = (clickEvent) => {
          clickEvent.preventDefault()
          clickEvent.stopPropagation()
          clickEvent.stopImmediatePropagation()
          window.removeEventListener('click', captureClick, true)
        }
        window.addEventListener('click', captureClick, true)
        setTimeout(() => {
          window.removeEventListener('click', captureClick, true)
          hasDragged = false
        }, 80)
      }
    }

    el.addEventListener('mousedown', onMouseDown)
    window.addEventListener('mousemove', onMouseMove)
    window.addEventListener('mouseup', onMouseUp)

    el._dragScrollCleanup = () => {
      el.removeEventListener('mousedown', onMouseDown)
      window.removeEventListener('mousemove', onMouseMove)
      window.removeEventListener('mouseup', onMouseUp)
      resizeObserver.disconnect()
    }
  },

  unmounted(el) {
    if (el._dragScrollCleanup) {
      el._dragScrollCleanup()
    }
  }
}

export default dragScroll

import { Filesystem, Directory } from '@capacitor/filesystem'
import { Share } from '@capacitor/share'

const isNative = (): boolean => !!(window as any).Capacitor?.isNativePlatform()

/**
 * Download a Blob as a file. Uses Capacitor Filesystem on native, <a download> on web.
 */
export async function downloadBlob(blob: Blob, filename: string): Promise<void> {
  if (isNative()) {
    const base64 = await blobToBase64(blob)

    const result = await Filesystem.writeFile({
      path: filename,
      data: base64,
      directory: Directory.Cache,
    })

    try {
      await Share.share({
        title: filename,
        url: result.uri,
      })
    } catch {
      // User dismissed share sheet — file is already saved
    }
  } else {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }
}

/**
 * Download a canvas as PNG. Uses Capacitor Filesystem on native, <a download> on web.
 */
export async function downloadCanvas(canvas: HTMLCanvasElement, filename: string): Promise<void> {
  if (isNative()) {
    const dataUrl = canvas.toDataURL('image/png')
    const base64 = dataUrl.split(',')[1]

    const result = await Filesystem.writeFile({
      path: filename,
      data: base64,
      directory: Directory.Cache,
    })

    try {
      await Share.share({
        title: filename,
        url: result.uri,
      })
    } catch {
      // User dismissed share sheet — file is already saved
    }
  } else {
    const link = document.createElement('a')
    link.download = filename
    link.href = canvas.toDataURL('image/png')
    link.click()
  }
}

function blobToBase64(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onloadend = () => {
      const result = reader.result as string
      resolve(result.split(',')[1])
    }
    reader.onerror = reject
    reader.readAsDataURL(blob)
  })
}

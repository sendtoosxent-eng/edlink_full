import { Dimensions, StyleSheet, View } from 'react-native';

const { width } = Dimensions.get('window');
const circle = width;

export function BrandBackdrop() {
  return (
    <>
      <View style={styles.top} />
      <View style={styles.bottom} />
    </>
  );
}

const styles = StyleSheet.create({
  top: {
    position: 'absolute',
    top: -circle / 2,
    left: 0,
    width: circle,
    height: circle,
    borderRadius: circle / 2,
    backgroundColor: '#FFCC33',
  },
  bottom: {
    position: 'absolute',
    bottom: -circle / 2,
    left: 0,
    width: circle,
    height: circle,
    borderRadius: circle / 2,
    backgroundColor: '#231E34',
  },
});

export const brandCircleSize = circle;

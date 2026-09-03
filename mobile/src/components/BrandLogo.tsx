import { Image, StyleSheet, Text, View } from 'react-native';

/** Replace `assets/brand-logo.png` with your logo. */
const brandLogo = require('../../assets/brand-logo.png');

export function BrandLogo({ large = false }: { large?: boolean }) {
  return (
    <View style={styles.lockup}>
      <View style={[styles.mark, large && styles.markLarge]}>
        <Image source={brandLogo} style={[styles.image, large && styles.imageLarge]} resizeMode="contain" />
      </View>
      <Text style={[styles.name, large && styles.nameLarge]}>Edlink.</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  lockup: { alignItems: 'center' },
  mark: {
    width: 62,
    height: 62,
    borderRadius: 31,
    borderWidth: 4,
    borderColor: '#231E34',
    backgroundColor: '#FFCC33',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  markLarge: { width: 130, height: 130, borderRadius: 65, borderWidth: 7 },
  image: { width: 38, height: 38 },
  imageLarge: { width: 78, height: 78 },
  name: { color: '#231E34', fontSize: 28, fontWeight: '900', marginTop: 8 },
  nameLarge: { fontSize: 42 },
});
